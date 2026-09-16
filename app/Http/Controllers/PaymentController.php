<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Booth;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display payments list
     */
    public function index(Request $request)
    {
        $query = Payment::with(['booking', 'client', 'user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        // Client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $payments = $query->latest('paid_at')->paginate(20)->withQueryString();

        // Calculate statistics
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount'),
            'total_cash_amount' => Payment::where('status', Payment::STATUS_COMPLETED)
                ->sum(DB::raw('COALESCE(cash_amount, CASE WHEN payment_method != "product_exchange" THEN amount ELSE 0 END)')),
            'total_product_amount' => Payment::where('status', Payment::STATUS_COMPLETED)
                ->sum(DB::raw('COALESCE(product_amount, CASE WHEN payment_method = "product_exchange" THEN amount ELSE 0 END)')),
            'pending_payments' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'failed_payments' => Payment::where('status', Payment::STATUS_FAILED)->count(),
            'today_payments' => Payment::whereDate('paid_at', today())->where('status', Payment::STATUS_COMPLETED)->sum('amount'),
            'this_month_payments' => Payment::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->where('status', Payment::STATUS_COMPLETED)
                ->sum('amount'),
        ];

        // Get unique payment methods
        $paymentMethods = Payment::distinct()->pluck('payment_method')->filter();
        $knownMethods = collect(['cash', 'bank_transfer', 'online', 'check', 'product_exchange', 'split']);
        $paymentMethods = $paymentMethods->concat($knownMethods)->unique()->values();

        // Get clients for filter
        $clients = \App\Models\Client::orderBy('company')->get();

        return view('payments.index', compact('payments', 'stats', 'paymentMethods', 'clients'));
    }

    /**
     * Create payment
     */
    public function store(Request $request)
    {
        $structure = $request->input('payment_structure', 'standard');

        if ($structure === 'split') {
            $request->validate([
                'booking_id' => 'required|exists:book,id',
                'cash_amount' => 'required|numeric|min:0.01',
                'cash_payment_method' => 'required|in:cash,bank_transfer,online,check',
                'product_amount' => 'required|numeric|min:0.01',
                'product_details' => 'required|string|max:2000',
                'transaction_id' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $cashAmount = round((float) $request->cash_amount, 2);
            $productAmount = round((float) $request->product_amount, 2);
            $totalAmount = round($cashAmount + $productAmount, 2);
            $paymentMethod = Payment::METHOD_SPLIT;
            $cashPaymentMethod = $request->cash_payment_method;
            $productDetails = $request->product_details;
        } elseif ($structure === 'product_exchange') {
            $request->validate([
                'booking_id' => 'required|exists:book,id',
                'product_amount' => 'required|numeric|min:0.01',
                'product_details' => 'required|string|max:2000',
                'transaction_id' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $cashAmount = 0.00;
            $productAmount = round((float) $request->product_amount, 2);
            $totalAmount = $productAmount;
            $paymentMethod = Payment::METHOD_PRODUCT_EXCHANGE;
            $cashPaymentMethod = null;
            $productDetails = $request->product_details;
        } else {
            $request->validate([
                'booking_id' => 'required|exists:book,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:cash,bank_transfer,online,check,product_exchange,split',
                'transaction_id' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            $totalAmount = round((float) $request->amount, 2);
            $paymentMethod = $request->payment_method;

            if ($paymentMethod === Payment::METHOD_PRODUCT_EXCHANGE) {
                $cashAmount = 0.00;
                $productAmount = $totalAmount;
                $cashPaymentMethod = null;
                $productDetails = $request->product_details ?? 'Product exchange deduction';
            } elseif ($paymentMethod === Payment::METHOD_SPLIT) {
                $cashAmount = round((float) ($request->cash_amount ?? ($totalAmount / 2)), 2);
                $productAmount = round($totalAmount - $cashAmount, 2);
                $cashPaymentMethod = $request->cash_payment_method ?? 'bank_transfer';
                $productDetails = $request->product_details ?? 'Product exchange deduction';
            } else {
                $cashAmount = $totalAmount;
                $productAmount = 0.00;
                $cashPaymentMethod = $paymentMethod;
                $productDetails = null;
            }
        }

        $booking = Book::with(['client', 'statusSetting'])->findOrFail($request->booking_id);

        // Calculate total booking amount if not set
        if (! $booking->total_amount) {
            $booking->total_amount = $booking->calculateTotalAmount();
        }

        // Get authenticated user ID - explicitly get from user object to ensure it's the ID, not username
        $user = Auth::user();
        $userId = $user ? (int) $user->id : null;

        if (! $userId) {
            return back()->withErrors(['error' => 'You must be logged in to record a payment.'])->withInput();
        }

        // Prepare combined notes with product exchange details for full transparency
        $combinedNotes = $request->notes;
        if (! empty($productDetails)) {
            $itemSummary = '[Product Exchange: $' . number_format($productAmount, 2) . ' - ' . $productDetails . ']';
            $combinedNotes = ! empty($combinedNotes) ? $combinedNotes . "\n" . $itemSummary : $itemSummary;
        }

        $payment = Payment::create([
            'booking_id' => $request->booking_id,
            'client_id' => $booking->clientid,
            'amount' => $totalAmount,
            'cash_amount' => $cashAmount,
            'product_amount' => $productAmount,
            'product_details' => $productDetails,
            'payment_method' => $paymentMethod,
            'cash_payment_method' => $cashPaymentMethod,
            'transaction_id' => $request->transaction_id,
            'status' => Payment::STATUS_COMPLETED,
            'notes' => $combinedNotes,
            'paid_at' => now(),
            'user_id' => $userId,
        ]);

        // Update booking payment amounts and status
        $booking->updatePaymentAmounts();

        // Update booth payment amounts based on payment
        // Distribute payment proportionally across booths using total credited amount
        $boothIds = json_decode($booking->boothid, true) ?? [];
        if (! empty($boothIds)) {
            $booths = Booth::whereIn('id', $boothIds)->lockForUpdate()->get();
            $totalBoothPrice = $booths->sum('price');

            foreach ($booths as $booth) {
                if ($totalBoothPrice > 0) {
                    // Calculate proportional payment for this booth
                    $boothProportion = ($booth->price / $totalBoothPrice);
                    $boothPaymentAmount = $totalAmount * $boothProportion;

                    // Update booth payment amounts
                    $currentDepositPaid = (float) ($booth->deposit_paid ?? 0);
                    $currentBalancePaid = (float) ($booth->balance_paid ?? 0);
                    $boothTotalPaid = $currentDepositPaid + $currentBalancePaid;

                    // Determine if this should be deposit or balance payment
                    $depositAmount = (float) ($booth->deposit_amount ?? 0);
                    $remainingDeposit = max(0, $depositAmount - $currentDepositPaid);

                    if ($remainingDeposit > 0) {
                        // Apply to deposit first
                        $depositPayment = min($boothPaymentAmount, $remainingDeposit);
                        $booth->deposit_paid = $currentDepositPaid + $depositPayment;
                        $booth->deposit_paid_date = now();
                        $boothPaymentAmount -= $depositPayment;
                    }

                    // Apply remaining to balance
                    if ($boothPaymentAmount > 0) {
                        $booth->balance_paid = $currentBalancePaid + $boothPaymentAmount;
                        $booth->balance_paid_date = now();
                    }

                    // Update booth status based on payment
                    $oldStatus = $booth->status;
                    $newTotalPaid = (float) ($booth->deposit_paid ?? 0) + (float) ($booth->balance_paid ?? 0);

                    if ($newTotalPaid >= $booth->price) {
                        $booth->status = Booth::STATUS_PAID;
                    } elseif ($newTotalPaid > 0) {
                        // Partially paid - keep as confirmed or reserved
                        if ($booth->status == Booth::STATUS_AVAILABLE) {
                            $booth->status = Booth::STATUS_CONFIRMED;
                        }
                    }

                    $booth->save();

                    // Send notification about payment and status change
                    if ($oldStatus != $booth->status) {
                        try {
                            \App\Services\NotificationService::notifyPaymentReceived($booth, $boothPaymentAmount);
                            \App\Services\NotificationService::notifyBoothStatusChange($booth, $oldStatus, $booth->status);
                        } catch (\Exception $e) {
                            \Log::error('Failed to send payment notification: '.$e->getMessage());
                        }
                    }
                }
            }
        }
        // Send payment receipt email
        try {
            $payment->load(['booking', 'booking.booth', 'booking.event', 'client']);
            if ($payment->client && $payment->client->email) {
            \Illuminate\Support\Facades\Mail::to($payment->client->email)
                ->send(new \App\Mail\PaymentReceiptMail($payment));
                
             // Log email sent
                \Log::info('Payment receipt email sent', [
               'payment_id' => $payment->id,
                    'client_email' => $payment->client->email,
                 'amount' => $payment->amount,
            ]);
            }
        } catch (\Exception $e) {
          \Log::warning('Failed to send payment receipt email: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
         'client_email' => $payment->client->email ?? 'N/A',
            ]);
            // Don't fail the payment if email fails
        }

        return redirect()->route('finance.payments.index')
            ->with('success', 'Payment recorded successfully');
    }

    /**
     * Generate invoice
     */
    public function invoice($id)
    {
        $payment = Payment::with(['booking', 'client', 'user'])->findOrFail($id);

        return view('payments.invoice', compact('payment'));
    }

    /**
     * Show payment form
     */
    public function create(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $booking = $bookingId ? Book::with(['client', 'statusSetting'])->findOrFail($bookingId) : null;

        return view('payments.create', compact('booking'));
    }

    /**
     * Refund a payment
     */
    public function refund(Request $request, $id)
    {
        $payment = Payment::with(['booking', 'booking.client', 'booking.statusSetting'])->findOrFail($id);

        // Validate payment can be refunded
        if ($payment->status === Payment::STATUS_REFUNDED) {
            return back()->with('error', 'This payment has already been refunded.');
        }

        if ($payment->status !== Payment::STATUS_COMPLETED) {
            return back()->with('error', 'Only completed payments can be refunded.');
        }

        try {
            \DB::beginTransaction();

            // Get authenticated user ID
            $user = Auth::user();
            $userId = $user ? (int) $user->id : null;

            if (! $userId) {
                \DB::rollBack();

                return back()->withErrors(['error' => 'You must be logged in to process a refund.']);
            }

            // Create refund payment entry (negative amount)
            $refundPayment = Payment::create([
                'booking_id' => $payment->booking_id,
                'client_id' => $payment->client_id,
                'amount' => -$payment->amount, // Negative amount for refund
                'payment_method' => $payment->payment_method,
                'status' => Payment::STATUS_REFUNDED,
                'transaction_id' => 'REFUND-'.$payment->transaction_id ?? 'REFUND-'.$payment->id,
                'notes' => 'Refund for Payment #'.$payment->id.($request->notes ? ': '.$request->notes : ''),
                'paid_at' => now(),
                'user_id' => $userId,
            ]);

            // Update original payment status
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'notes' => ($payment->notes ? $payment->notes.' | ' : '').'Refunded on '.now()->format('Y-m-d H:i:s'),
            ]);

            // Revert booth status from paid to confirmed (or reserved if no confirmation)
            // Use lock to prevent race conditions
            if ($payment->booking) {
                $boothIds = json_decode($payment->booking->boothid, true) ?? [];
                if (! empty($boothIds)) {
                    Booth::whereIn('id', $boothIds)
                        ->where('status', Booth::STATUS_PAID)
                        ->lockForUpdate()
                        ->update([
                            'status' => Booth::STATUS_CONFIRMED, // Revert to confirmed, not available
                        ]);
                }
            }

            \DB::commit();

            return redirect()->route('finance.payments.index')
                ->with('success', 'Payment refunded successfully. Refund payment #'.$refundPayment->id.' created.');
        } catch (\Exception $e) {
            \DB::rollBack();

            return back()->with('error', 'Error processing refund: '.$e->getMessage());
        }
    }

    /**
     * Void a payment (cancel before completion)
     */
    public function void(Request $request, $id)
    {
        $payment = Payment::with(['booking', 'booking.client', 'booking.statusSetting'])->findOrFail($id);

        // Validate payment can be voided
        if ($payment->status === Payment::STATUS_REFUNDED) {
            return back()->with('error', 'This payment has already been refunded and cannot be voided.');
        }

        if ($payment->status === Payment::STATUS_FAILED) {
            return back()->with('error', 'This payment has already failed.');
        }

        try {
            \DB::beginTransaction();

            // Store original status before update
            $originalStatus = $payment->status;

            // Update payment status to failed (voided)
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'notes' => ($payment->notes ? $payment->notes.' | ' : '').'VOIDED on '.now()->format('Y-m-d H:i:s').($request->notes ? ': '.$request->notes : ''),
            ]);

            // Revert booth status if payment was completed (use lock to prevent race conditions)
            if ($originalStatus === Payment::STATUS_COMPLETED && $payment->booking) {
                $boothIds = json_decode($payment->booking->boothid, true) ?? [];
                if (! empty($boothIds)) {
                    Booth::whereIn('id', $boothIds)
                        ->where('status', Booth::STATUS_PAID)
                        ->lockForUpdate()
                        ->update([
                            'status' => Booth::STATUS_CONFIRMED, // Revert to confirmed
                        ]);
                }
            }

            \DB::commit();

            return redirect()->route('finance.payments.index')
                ->with('success', 'Payment voided successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();

            return back()->with('error', 'Error voiding payment: '.$e->getMessage());
        }
    }
}
