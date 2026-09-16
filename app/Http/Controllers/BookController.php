<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\Book;
use App\Models\Booth;
use App\Models\Category;
use App\Models\Client;
use App\Models\FloorPlan;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\BookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class BookController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private BookService $bookService
    ) {}

    /**
     * Display a listing of bookings
     */
    public function index(Request $request)
    {
        // AJAX: replace bookings list only (filters) — must run before lazy-load (page) branch
        if ($request->wantsJson() && $request->boolean('books_list_partial')) {
            return $this->bookListPartialJson($request);
        }

        // If AJAX request for lazy loading (check for page parameter or X-Requested-With header)
        if (($request->ajax() || $request->wantsJson() || $request->hasHeader('X-Requested-With')) && $request->has('page')) {
            return $this->lazyLoad($request);
        }

        $filters = [
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'type' => $request->input('type'),
            'floor_plan_id' => $request->input('floor_plan_id'),
            'status' => $request->input('status'),
            'amount_min' => $request->input('amount_min'),
            'amount_max' => $request->input('amount_max'),
            'booth_count_min' => $request->input('booth_count_min'),
            'date_range' => $request->input('date_range', 'all'),
        ];

        $groupBy = $request->input('group_by', 'none');

        if ($groupBy !== 'none') {
            $result = $this->bookService->getGroupedBookings($filters, $groupBy);
            $books = $result['books'];
            $groupedBooks = $result['groupedBooks'];
            $boothsByBookId = $result['boothsByBookId'];
            $total = $books->count();
        } else {
            $result = $this->bookService->getBookings($filters, 20, 1);
            $books = $result['books'];
            $total = $result['total'];
            $boothsByBookId = $result['boothsByBookId'];
            $groupedBooks = [];
        }

        $restrictToOwnBookings = $this->restrictToOwnBookings();
        $floorPlans = FloorPlan::where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();

        try {
            $statusSettings = \App\Models\BookingStatusSetting::getActiveStatuses();
        } catch (\Exception $e) {
            $statusSettings = collect([]);
        }

        $dateRange = $filters['date_range'];

        return view('books.index', compact('books', 'total', 'groupBy', 'dateRange', 'groupedBooks', 'restrictToOwnBookings', 'boothsByBookId', 'floorPlans', 'statusSettings'));
    }

    /**
     * JSON fragment for #bookingsContainer when filters change (no full page reload).
     */
    private function bookListPartialJson(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'type' => $request->input('type'),
            'floor_plan_id' => $request->input('floor_plan_id'),
            'status' => $request->input('status'),
            'amount_min' => $request->input('amount_min'),
            'amount_max' => $request->input('amount_max'),
            'booth_count_min' => $request->input('booth_count_min'),
            'date_range' => $request->input('date_range', 'all'),
        ];

        $groupBy = $request->input('group_by', 'none');

        if ($groupBy !== 'none') {
            $result = $this->bookService->getGroupedBookings($filters, $groupBy);
            $books = $result['books'];
            $groupedBooks = $result['groupedBooks'];
            $boothsByBookId = $result['boothsByBookId'];
            $total = $books->count();
        } else {
            $result = $this->bookService->getBookings($filters, 20, 1);
            $books = $result['books'];
            $total = $result['total'];
            $boothsByBookId = $result['boothsByBookId'];
            $groupedBooks = [];
        }

        $html = view('books.partials.index-bookings-container', compact(
            'books',
            'groupBy',
            'groupedBooks',
            'boothsByBookId'
        ))->render();

        $lazyLoadMoreAvailable = ($groupBy === 'none' && $total > $books->count());

        return response()->json([
            'success' => true,
            'html' => $html,
            'activeFilterCount' => $this->countActiveBookFilters($request),
            'groupBy' => $groupBy,
            'hasMore' => $lazyLoadMoreAvailable,
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function countActiveBookFilters(Request $request): int
    {
        $n = 0;
        if ($request->filled('search')) {
            $n++;
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $n++;
        }
        if ($request->filled('type')) {
            $n++;
        }
        if ($request->filled('floor_plan_id')) {
            $n++;
        }
        if ($request->filled('status')) {
            $n++;
        }
        if ($request->filled('amount_min') || $request->filled('amount_max')) {
            $n++;
        }
        if ($request->filled('booth_count_min')) {
            $n++;
        }
        if ($request->input('date_range') && $request->input('date_range') !== 'all') {
            $n++;
        }

        return $n;
    }

    /**
     * Lazy load bookings (AJAX endpoint)
     */
    public function lazyLoad(Request $request)
    {
        $with = ['client', 'user', 'floorPlan', 'statusSetting'];
        if (\Schema::hasTable('events')) {
            $with[] = 'floorPlan.event';
        }
        $query = Book::with($with);

        if ($this->restrictToOwnBookings()) {
            $query->where('userid', auth()->id());
        }

        // Search functionality (exact same as index)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            })->orWhereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%");
            });
        }

        // Date filter (exact same as index)
        if ($request->filled('date_from')) {
            $query->whereDate('date_book', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_book', '<=', $request->date_to);
        }

        // Type filter (exact same as index)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Floor plan filter
        if ($request->filled('floor_plan_id')) {
            $query->where('floor_plan_id', $request->floor_plan_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        // Amount range filter
        if ($request->filled('amount_min') && is_numeric($request->amount_min)) {
            $query->where('total_amount', '>=', (float) $request->amount_min);
        }
        if ($request->filled('amount_max') && is_numeric($request->amount_max)) {
            $query->where('total_amount', '<=', (float) $request->amount_max);
        }

        // Min booth count filter
        if ($request->filled('booth_count_min') && is_numeric($request->booth_count_min) && (int) $request->booth_count_min > 0) {
            $driver = $query->getConnection()->getDriverName();
            $minBooths = (int) $request->booth_count_min;
            if ($driver === 'mysql') {
                $query->whereRaw('JSON_LENGTH(COALESCE(boothid, \'[]\')) >= ?', [$minBooths]);
            } elseif ($driver === 'sqlite') {
                $query->whereRaw('json_array_length(COALESCE(boothid, \'[]\')) >= ?', [$minBooths]);
            }
        }
        // Group By filter (exact same as index)
        $dateRange = $request->input('date_range', 'all');

        // Apply date range filter if specified
        if ($dateRange !== 'all') {
            $now = now();
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('date_book', $now->toDateString());
                    break;
                case '3days':
                    $query->whereDate('date_book', '>=', $now->copy()->subDays(3)->toDateString());
                    break;
                case '7days':
                    $query->whereDate('date_book', '>=', $now->copy()->subDays(7)->toDateString());
                    break;
                case '14days':
                    $query->whereDate('date_book', '>=', $now->copy()->subDays(14)->toDateString());
                    break;
                case 'more':
                    $query->whereDate('date_book', '<', $now->copy()->subDays(14)->toDateString());
                    break;
            }
        }

        // Use same ordering and limit as initial load
        $page = $request->input('page', 1);
        $perPage = 20; // Same as initial load limit(20)
        $offset = ($page - 1) * $perPage;

        // Get total before pagination
        $total = $query->count();

        // Use exact same ordering as index method
        $books = $query->latest('date_book')->offset($offset)->limit($perPage)->get();
        $hasMore = ($offset + $books->count()) < $total;

        $boothsByBookId = $this->loadBoothsForBooks($books);

        $view = $request->input('view', 'table'); // 'table' or 'card'
        $groupBy = $request->input('group_by', 'none');
        $html = '';

        foreach ($books as $i => $book) {
            $rowNumber = $offset + $i + 1;
            // Ensure relationships are loaded (same as initial load)
            if (! $book->relationLoaded('client')) {
                $book->load('client');
            }
            if (! $book->relationLoaded('user')) {
                $book->load('user');
            }

            $boothIds = json_decode($book->boothid, true) ?? [];

            try {
                $statusSetting = $book->statusSetting ?? \App\Models\BookingStatusSetting::getByCode($book->status ?? 1);
                $statusColor = $statusSetting ? $statusSetting->status_color : '#6c757d';
                $statusTextColor = $statusSetting && $statusSetting->text_color ? $statusSetting->text_color : '#ffffff';
                $statusName = $statusSetting ? $statusSetting->status_name : 'Pending';
            } catch (\Exception $e) {
                $statusColor = '#6c757d';
                $statusTextColor = '#ffffff';
                $statusName = 'Pending';
            }

            $totalAmount = $book->total_amount ?? \App\Models\Booth::whereIn('id', $boothIds)->sum('price');
            $paidAmount = $book->paid_amount ?? 0;
            $balanceAmount = $book->balance_amount ?? ($totalAmount - $paidAmount);

            if ($view === 'table') {
                $html .= view('books.partials.table-row', compact('book', 'rowNumber', 'statusColor', 'statusTextColor', 'statusName', 'totalAmount', 'balanceAmount', 'boothsByBookId'))->render();
            } else {
                $html .= view('books.partials.card', compact('book', 'boothsByBookId', 'rowNumber'))->render();
            }
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'hasMore' => $hasMore,
            'total' => $total,
            'loaded' => $offset + $books->count(),
            'page' => $page,
            'perPage' => $perPage,
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Show the form for creating a new booking
     */
    public function create(Request $request)
    {
        $clients = Client::orderBy('company')->get();

        // Get floor plan filter (from query param)
        $floorPlanId = $request->input('floor_plan_id');

        // Get all floor plans for selector
        $floorPlans = \App\Models\FloorPlan::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        // Filter booths by floor plan if specified
        $boothsQuery = Booth::whereIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN]);

        if ($floorPlanId) {
            $boothsQuery->where('floor_plan_id', $floorPlanId);
        }

        $booths = $boothsQuery->orderBy('booth_number')->get();

        $categories = Category::where('status', 1)->orderBy('name')->get();

        // Group booths by first letter (A-Z) for tab view
        $boothsByLetter = [];

        foreach ($booths as $booth) {
            // Get first character of booth number (uppercase)
            $firstChar = strtoupper(substr(trim($booth->booth_number), 0, 1));

            // If first character is not a letter, put it in "#" group
            if (! ctype_alpha($firstChar)) {
                $firstChar = '#';
            }

            if (! isset($boothsByLetter[$firstChar])) {
                $boothsByLetter[$firstChar] = collect();
            }

            $boothsByLetter[$firstChar]->push($booth);
        }

        // Sort by letter (A-Z, then #)
        ksort($boothsByLetter);

        // Move # to the end if it exists
        if (isset($boothsByLetter['#'])) {
            $numbersGroup = $boothsByLetter['#'];
            unset($boothsByLetter['#']);
            $boothsByLetter['#'] = $numbersGroup;
        }

        // Convert to format expected by view
        $boothsByCategory = [];
        foreach ($boothsByLetter as $letter => $boothCollection) {
            $boothsByCategory[$letter] = [
                'category' => (object) ['id' => $letter, 'name' => $letter, 'avatar' => null],
                'booths' => $boothCollection,
            ];
        }

        // Get current floor plan if specified
        $currentFloorPlan = $floorPlanId ? \App\Models\FloorPlan::find($floorPlanId) : null;

        // Single responsive view: one template for all screen sizes (Laravel responsive approach)
        return view('books.create', compact('clients', 'booths', 'categories', 'floorPlans', 'floorPlanId', 'currentFloorPlan', 'boothsByCategory'));
    }

    /**
     * Get booths for modal (AJAX endpoint)
     */
    public function getBooths(Request $request)
    {
        $floorPlanId = $request->input('floor_plan_id');

        // Filter booths by floor plan if specified
        $boothsQuery = Booth::whereIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN]);

        if ($floorPlanId) {
            $boothsQuery->where('floor_plan_id', $floorPlanId);
        }

        $booths = $boothsQuery->orderBy('booth_number')->get();

        $html = '';
        if ($booths->count() > 0) {
            foreach ($booths as $booth) {
                $html .= '<div class="col-md-6 mb-2">';
                $html .= '<div class="booth-option-modal border rounded p-2" data-booth-id="'.$booth->id.'" data-price="'.($booth->price ?? 0).'" style="cursor: pointer; transition: all 0.2s; background: white;">';
                $html .= '<label class="mb-0 w-100" style="cursor: pointer;">';
                $html .= '<input type="checkbox" name="booth_ids[]" value="'.$booth->id.'" class="modal-booth-checkbox" onchange="modalUpdateSelection()">';
                $html .= '<strong class="text-primary">'.e($booth->booth_number).'</strong>';
                $html .= '<span class="badge badge-'.($booth->getStatusColor() ?? 'secondary').' ml-2" style="font-size: 0.75rem;">'.e($booth->getStatusLabel() ?? 'Available').'</span>';
                if ($booth->category) {
                    $html .= '<br><small class="text-muted ml-4" style="font-size: 0.8125rem;"><i class="fas fa-folder"></i> '.e($booth->category->name).'</small>';
                }
                $html .= '<div class="mt-1 text-right"><strong class="text-success" style="font-size: 0.875rem;">$'.number_format($booth->price ?? 0, 2).'</strong></div>';
                $html .= '</label></div></div>';
            }
        } else {
            $html = '<div class="col-12"><div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>No available booths found.</div></div>';
        }

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Store a newly created booking
     */
    public function store(CreateBookingRequest $request)
    {
        try {
            $validated = $request->validated();

            // Get affiliate user ID from session (if customer came from affiliate link)
            $affiliateUserId = null;
            if (session()->has('affiliate_user_id')) {
                $affiliateUserId = session('affiliate_user_id');
                // Check if affiliate session is still valid (not expired)
                if (session()->has('affiliate_expires_at') && now()->lt(session('affiliate_expires_at'))) {
                    $affiliateUserId = session('affiliate_user_id');
                } else {
                    $affiliateUserId = null; // Session expired
                }
            }

            if ($affiliateUserId && Schema::hasColumn('book', 'affiliate_user_id')) {
                $validated['affiliate_user_id'] = $affiliateUserId;
            }

            // Map booking type to booth status
            $bookingType = $validated['type'] ?? 1;
            $boothStatus = ($bookingType == 2) ? Booth::STATUS_CONFIRMED : Booth::STATUS_RESERVED;

            // Get default booking status
            $bookingStatus = null;
            if (Schema::hasColumn('book', 'status')) {
                try {
                    $defaultStatus = \App\Models\BookingStatusSetting::getDefault();
                    $bookingStatus = $defaultStatus ? $defaultStatus->status_code : Book::STATUS_PENDING;
                } catch (\Exception $e) {
                    $bookingStatus = Book::STATUS_PENDING;
                }
            }

            if ($bookingStatus !== null && Schema::hasColumn('book', 'status')) {
                $validated['status'] = $bookingStatus;
            }

            // Create booking
            $booking = $this->bookingService->createBooking($validated);

            // Update booth statuses based on booking type
         Booth::whereIn('id', $validated['booth_ids'])
         ->whereIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])
                ->lockForUpdate()
                ->update([
          'status' => $boothStatus,
        ]);
            // Send booking confirmation email
            try {
                $booking->load(['client', 'booth', 'event']);
          if ($booking->client && $booking->client->email) {
                    \Illuminate\Support\Facades\Mail::to($booking->client->email)
                ->send(new \App\Mail\BookingConfirmationMail($booking));
                    
             // Log email sent in timeline
                  if (class_exists('\App\Models\BookingTimeline')) {
                     \App\Models\BookingTimeline::createEntry(
                $booking->booth ? $booking->booth->id : null,
               'booking_confirmation_sent',
                      "Booking confirmation email sent to {$booking->client->email}",
                            null,
                          $booking->id
                        );
                 }
           }
        } catch (\Exception $e) {
                \Log::warning('Failed to send booking confirmation email: ' . $e->getMessage(), [
                 'booking_id' => $booking->id,
            'client_email' => $booking->client->email ?? 'N/A',
          ]);
            // Don't fail the booking if email fails
            }

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully.',
                    'booking' => [
                        'id' => $booking->id,
                        'client' => $booking->client ? ($booking->client->company ?? $booking->client->name) : 'N/A',
                    ],
                ]);
            }

            return redirect()->route('books.index')
                ->with('success', 'Booking created successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors($e->errors());

        } catch (\Exception $e) {
            \Log::error('Booking creation failed: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'client_id' => $request->input('clientid'),
                'booth_ids' => $request->input('booth_ids', []),
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating booking: '.$e->getMessage(),
                    'errors' => ['error' => [$e->getMessage()]],
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Error creating booking: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified booking
     */
    public function show(Book $book)
    {
        if (! $this->canManageBooking($book)) {
            abort(403, 'You can only view your own bookings. This booking was created by another user.');
        }

        $book->load(['client', 'user', 'payments', 'statusSetting', 'floorPlan']);
        if (\Schema::hasTable('events')) {
            $book->load('floorPlan.event');
        }

        // Calculate amounts if not set
        if (! $book->total_amount) {
            $book->total_amount = $book->calculateTotalAmount();
            $book->save();
        }
        if (! $book->paid_amount) {
            $book->paid_amount = $book->calculatePaidAmount();
            $book->balance_amount = $book->total_amount - $book->paid_amount;
            $book->save();
        }

        $booths = $this->bookService->getBoothsForBooking($book);
        $payments = $book->payments()->with('user')->latest('paid_at')->get();

        try {
            $statusSettings = \App\Models\BookingStatusSetting::getActiveStatuses();
        } catch (\Exception $e) {
            $statusSettings = collect([]);
        }

        // Return JSON for AJAX requests (popup)
        if (request()->ajax() || request()->wantsJson()) {
            try {
                $statusSetting = $book->statusSetting ?? \App\Models\BookingStatusSetting::getByCode($book->status ?? 1);
                $statusColor = $statusSetting ? $statusSetting->status_color : '#6c757d';
                $statusTextColor = $statusSetting && $statusSetting->text_color ? $statusSetting->text_color : '#ffffff';
                $statusName = $statusSetting ? $statusSetting->status_name : 'Pending';
            } catch (\Exception $e) {
                $statusColor = '#6c757d';
                $statusTextColor = '#ffffff';
                $statusName = 'Pending';
            }

            $html = view('books.partials.modal-booking-info', compact('book', 'booths'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'book' => [
                    'id' => $book->id,
                    'date_book' => $book->date_book ? $book->date_book->format('F d, Y h:i A') : 'N/A',
                    'date_book_date' => $book->date_book ? $book->date_book->format('F d, Y') : 'N/A',
                    'date_book_time' => $book->date_book ? $book->date_book->format('h:i A') : 'N/A',
                    'type' => $book->type,
                    'type_name' => $book->type == 1 ? 'Regular' : ($book->type == 2 ? 'Special' : ($book->type == 3 ? 'Temporary' : 'Unknown')),
                    'status' => $book->status ?? 1,
                    'status_name' => $statusName,
                    'status_color' => $statusColor,
                    'status_text_color' => $statusTextColor,
                    'total_amount' => $book->total_amount ?? $booths->sum('price') ?? 0,
                    'paid_amount' => $book->paid_amount ?? 0,
                    'balance_amount' => $book->balance_amount ?? (($book->total_amount ?? $booths->sum('price') ?? 0) - ($book->paid_amount ?? 0)),
                    'notes' => $book->notes ?? '',
                    'user' => $book->user ? [
                        'id' => $book->user->id,
                        'username' => $book->user->username,
                        'avatar' => $book->user->avatar,
                    ] : null,
                    'client' => $book->client ? [
                        'id' => $book->client->id,
                        'name' => $book->client->name,
                        'company' => $book->client->company ?? '',
                        'position' => $book->client->position ?? '',
                        'phone_number' => $book->client->phone_number ?? '',
                        'email' => $book->client->email ?? '',
                        'address' => $book->client->address ?? '',
                    ] : null,
                    'booths' => $booths->map(function ($booth) {
                        return [
                            'id' => $booth->id,
                            'booth_number' => $booth->booth_number,
                            'price' => $booth->price ?? 0,
                            'category' => $booth->category ? $booth->category->name : null,
                            'floor_plan' => $booth->floorPlan ? $booth->floorPlan->name : null,
                        ];
                    })->toArray(),
                    'booth_count' => count($booths),
                    'payments' => $payments->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'paid_at' => $payment->paid_at ? $payment->paid_at->format('F d, Y h:i A') : 'N/A',
                            'payment_method' => $payment->payment_method ?? 'N/A',
                            'user' => $payment->user ? $payment->user->username : 'System',
                        ];
                    })->toArray(),
                ],
            ]);
            $users = auth()->check() && auth()->user()->isAdmin()
                ? User::where('status', 1)->orderBy('username')->get()
                : collect([]);

            return view('books.show', compact('book', 'booths', 'payments', 'statusSettings', 'users'));
        }

        $users = auth()->check() && auth()->user()->isAdmin()
            ? User::where('status', 1)->orderBy('username')->get()
            : collect([]);

        return view('books.show', compact('book', 'booths', 'payments', 'statusSettings', 'users'));
    }

    /**
     * Update booking status
     */
    public function updateStatus(UpdateBookingStatusRequest $request, Book $book)
    {
        if (! $this->canManageBooking($book)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this booking status.',
            ], 403);
        }

        try {
            $booking = $this->bookingService->updateBookingStatus(
                $book,
                $request->status,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking status updated successfully',
                'status' => $booking->status,
                'status_label' => $booking->status_label,
            ]);
        } catch (\Exception $e) {
            \Log::error('Booking status update failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reassign booking ownership (Booked by who) to another team member
     */
    public function updateBookedBy(Request $request, Book $book)
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can reassign booking ownership.',
            ], 403);
        }

        $request->validate([
            'userid' => 'required|integer|exists:user,id',
        ]);

        try {
            $booking = $this->bookingService->reassignBooking($book, (int) $request->userid);

            return response()->json([
                'success' => true,
                'message' => 'Booking owner reassigned successfully to ' . ($booking->user->username ?? 'User #' . $request->userid),
                'user' => [
                    'id' => $booking->user->id,
                    'username' => $booking->user->username,
                    'avatar' => $booking->user->avatar,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Booking reassignment failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign booking: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified booking
     */
    public function edit(Book $book)
    {
        if (! $this->canManageBooking($book)) {
            abort(403, 'You can only edit your own bookings. This booking was created by another user.');
        }
        $book->load(['client', 'user']);
        $boothIds = json_decode($book->boothid, true) ?? [];
        $currentBooths = ! empty($boothIds) ? Booth::whereIn('id', $boothIds)->get() : collect([]);

        $clients = Client::orderBy('company')->get();
        $allBooths = Booth::orderBy('booth_number')->get();
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $users = auth()->check() && auth()->user()->isAdmin()
            ? User::where('status', 1)->orderBy('username')->get()
            : collect([]);

        return view('books.edit', compact('book', 'clients', 'allBooths', 'currentBooths', 'boothIds', 'categories', 'users'));
    }

    /**
     * Update the specified booking
     */
    public function update(UpdateBookingRequest $request, Book $book)
    {
        if (! $this->canManageBooking($book)) {
            abort(403, 'You can only update your own bookings. This booking was created by another user.');
        }

        try {
            $validated = $request->validated();

            // Only admins can reassign booking ownership
            if ((! auth()->check() || ! auth()->user()->isAdmin()) && isset($validated['userid'])) {
                unset($validated['userid']);
            }

            // Auto-set date_book to current date/time if not provided
            if (empty($validated['date_book'])) {
                $validated['date_book'] = $book->date_book ?? now();
            }

            // Update booking using service
            $booking = $this->bookingService->updateBooking($book, $validated);

            return redirect()->route('books.show', $booking)
                ->with('success', 'Booking updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());

        } catch (\Exception $e) {
            \Log::error('Booking update failed: '.$e->getMessage(), [
                'booking_id' => $book->id,
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Error updating booking: '.$e->getMessage()]);
        }
    }

    /**
     * Return a snapshot of the booking for Undo (restore) after delete.
     * Only allowed if the user can manage this booking.
     */
    public function forRestore(Book $book)
    {
        if (! $this->canManageBooking($book)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $boothIds = json_decode($book->boothid, true) ?? [];

        return response()->json([
            'success' => true,
            'snapshot' => [
                'clientid' => $book->clientid,
                'booth_ids' => array_values($boothIds),
                'floor_plan_id' => $book->floor_plan_id,
                'event_id' => $book->event_id,
                'userid' => $book->userid,
                'date_book' => $book->date_book ? $book->date_book->toIso8601String() : null,
                'type' => $book->type,
                'notes' => $book->notes,
                'status' => $book->status,
                'total_amount' => $book->total_amount ? (float) $book->total_amount : null,
                'paid_amount' => $book->paid_amount ? (float) $book->paid_amount : 0,
                'payment_due_date' => $book->payment_due_date ? $book->payment_due_date->format('Y-m-d') : null,
                'affiliate_user_id' => $book->affiliate_user_id,
            ],
        ]);
    }

    /**
     * Restore a deleted booking from a snapshot (Undo).
     */
    public function restore(Request $request)
    {
        $request->validate([
            'snapshot' => 'required|array',
            'snapshot.clientid' => 'required|exists:client,id',
            'snapshot.booth_ids' => 'required|array|min:1',
            'snapshot.booth_ids.*' => 'integer|exists:booth,id',
        ]);

        $snapshot = $request->input('snapshot');
        $snapshot['date_book'] = isset($snapshot['date_book']) ? $snapshot['date_book'] : now();

        try {
            $booking = $this->bookingService->restoreBooking($snapshot);

            return response()->json([
                'success' => true,
                'message' => 'Booking restored.',
                'booking' => ['id' => $booking->id],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Booking restore failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified booking
     */
    public function destroy(Book $book)
    {
        if (! $this->canManageBooking($book)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own bookings. This booking was created by another user.',
            ], 403);
        }

        try {
            // Check if booking has payment
            $payment = \App\Models\Payment::where('booking_id', $book->id)
                ->where('status', \App\Models\Payment::STATUS_COMPLETED)
                ->first();

            if ($payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete booking with completed payment. Please refund payment first.',
                ], 400);
            }

            // Handle paid booths separately (keep them as-is, just remove booking reference)
            $boothIds = json_decode($book->boothid, true) ?? [];
            if (! empty($boothIds)) {
                $booths = Booth::whereIn('id', $boothIds)->get();
                $paidBooths = [];

                foreach ($booths as $booth) {
                    if ($booth->status === Booth::STATUS_PAID) {
                        $paidBooths[] = $booth->booth_number;
                        // Keep paid booths as-is, just remove booking reference
                        $booth->update([
                            'bookid' => null,
                        ]);
                    }
                }

                if (! empty($paidBooths)) {
                    \Log::warning('Booking deleted with paid booths', [
                        'booking_id' => $book->id,
                        'paid_booths' => $paidBooths,
                    ]);
                }
            }

            // Delete booking (service will handle booth release for non-paid booths)
            $this->bookingService->deleteBooking($book);

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Booking deletion failed: '.$e->getMessage(), [
                'booking_id' => $book->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all booking records (requires password verification)
     */
    public function deleteAll(Request $request)
    {
        // Only allow admin users
        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password
        $user = auth()->user();
        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password. Please try again.',
            ], 401);
        }

        try {
            DB::beginTransaction();

            $totalCount = Book::count();

            if ($totalCount === 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No booking records to delete.',
                ], 400);
            }

            // Get all bookings to release booths
            $books = Book::all();
            $boothIdsToRelease = [];

            foreach ($books as $book) {
                $boothIds = json_decode($book->boothid, true) ?? [];
                $boothIdsToRelease = array_merge($boothIdsToRelease, $boothIds);
            }

            // Release all booths (set status to available) - but NOT if they are PAID
            if (! empty($boothIdsToRelease)) {
                $booths = Booth::whereIn('id', array_unique($boothIdsToRelease))->get();
                $paidBooths = [];

                foreach ($booths as $booth) {
                    if ($booth->status === Booth::STATUS_PAID) {
                        $paidBooths[] = $booth->booth_number;
                        // Keep paid booths as-is, just remove booking reference
                        $booth->update([
                            'bookid' => null,
                        ]);
                    } else {
                        // Release non-paid booths
                        $booth->update([
                            'status' => Booth::STATUS_AVAILABLE,
                            'client_id' => null,
                            'userid' => null,
                            'bookid' => null,
                        ]);
                    }
                }

                if (! empty($paidBooths)) {
                    \Log::warning('All bookings deleted with paid booths', [
                        'paid_booths' => $paidBooths,
                        'deleted_by' => auth()->user()->id ?? null,
                    ]);
                }
            }

            // Delete all bookings
            Book::query()->delete();

            DB::commit();

            \Log::info('All booking records deleted', [
                'total_deleted' => $totalCount,
                'deleted_by' => auth()->user()->id ?? null,
                'deleted_by_username' => auth()->user()->username ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "All {$totalCount} booking record(s) deleted successfully.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delete all bookings failed: '.$e->getMessage(), [
                'deleted_by' => auth()->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting records: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Booking action - Creates a new booking with client creation
     * This matches the Yii actionBooking logic
     */
    public function booking(Request $request)
    {
        $data = $request->input('data');

        if (! isset($data)) {
            return response()->json([
                'status' => 403,
                'message' => 'Please Check Data Before Submit',
            ], 403);
        }

        // Replace @rp4and with & (Yii code does this)
        $data = str_replace('@rp4and', '&', $data);
        $data = json_decode($data, true);

        if (! is_array($data)) {
            return response()->json([
                'status' => 403,
                'message' => 'Invalid data format',
            ], 403);
        }

        // Validate required fields - ALL client information is now required for successful booking
        $requiredFields = [
            'book',
            'inputCpnName',      // Company name
            'inputName',         // Client name
            'inputPhone',        // Phone number
            'inputEmail',        // Email (NEW - required)
            'inputAddress',      // Address (NEW - required)
            'booth',              // Booth IDs
        ];

        // Always require all fields for any booking type (no exceptions)
        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || empty($data[$field])) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Please fill in all required client information fields (Name, Company, Phone, Email, Address)',
                ], 403);
            }
        }

        // Validate email format
        if (isset($data['inputEmail']) && ! filter_var($data['inputEmail'], FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 403,
                'message' => 'Please enter a valid email address',
            ], 403);
        }

        // Check category limits
        if (isset($data['inputCategory']) && ! empty($data['inputCategory'])) {
            if ($this->isLimitCategory($data['inputCategory'], count($data['booth']), 1)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Limit Category Contact Admin',
                ], 403);
            }
        }

        if (isset($data['inputSubCategory']) && ! empty($data['inputSubCategory'])) {
            if ($this->isLimitCategory($data['inputSubCategory'], count($data['booth']), 2)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Limit Sub Category Contact Admin',
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            // Check if all booths are available (with lock to prevent race conditions)
            $unavailableBooths = Booth::whereIn('id', $data['booth'])
                ->whereNotIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])
                ->lockForUpdate()
                ->get();

            if ($unavailableBooths->count() > 0) {
                DB::rollBack();
                $boothNumbers = $unavailableBooths->pluck('booth_number')->implode(', ');

                return response()->json([
                    'status' => 403,
                    'message' => 'Booth(s) not available: '.$boothNumbers,
                ], 403);
            }

            // Verify all booths exist
            $boothsCount = Booth::whereIn('id', $data['booth'])->count();
            if ($boothsCount !== count($data['booth'])) {
                DB::rollBack();

                return response()->json([
                    'status' => 403,
                    'message' => 'One or more selected booths do not exist.',
                ], 403);
            }

            // Verify all booths are from the same floor plan
            $booths = Booth::whereIn('id', $data['booth'])->get();
            $floorPlanIds = $booths->pluck('floor_plan_id')->unique()->filter();
            if ($floorPlanIds->count() > 1) {
                DB::rollBack();

                return response()->json([
                    'status' => 403,
                    'message' => 'All booths must be from the same floor plan.',
                ], 403);
            }

            $userid = auth()->user()->id;
            $clientID = 0;

            // Create client with ALL required information (all fields are now required)
            $clientData = [
                'company' => $data['inputCpnName'],
                'name' => $data['inputName'],
                'phone_number' => $data['inputPhone'],
                'email' => $data['inputEmail'],
                'address' => $data['inputAddress'],
                'position' => $data['inputPosition'] ?? null,
                'sex' => isset($data['inputSex']) && ! empty($data['inputSex']) ? (int) $data['inputSex'] : null,
                'tax_id' => $data['inputTaxId'] ?? null,
                'website' => $data['inputWebsite'] ?? null,
                'notes' => $data['inputNotes'] ?? null,
            ];

            // Check if client already exists by email or phone (avoid duplicates)
            $existingClient = Client::where('email', $clientData['email'])
                ->orWhere('phone_number', $clientData['phone_number'])
                ->first();

            if ($existingClient) {
                // Update existing client with latest information
                $existingClient->update($clientData);
                $clientID = $existingClient->id;
            } else {
                // Create new client
                $client = Client::create($clientData);
                $clientID = $client->id;
            }

            // Map booking type to status (1=Regular=RESERVED, 2=Special=CONFIRMED, 3=Temporary=RESERVED)
            $bookingType = $data['book'] ?? 3;
            $boothStatus = ($bookingType == 2) ? Booth::STATUS_CONFIRMED : Booth::STATUS_RESERVED;

            // Get floor plan and event from first booth (all booths should be from same floor plan)
            $booths = Booth::whereIn('id', $data['booth'])->get();

            // Verify all booths are from the same floor plan
            $floorPlanIds = $booths->pluck('floor_plan_id')->unique()->filter();
            if ($floorPlanIds->count() > 1) {
                DB::rollBack();

                return response()->json([
                    'status' => 403,
                    'message' => 'All booths must be from the same floor plan.',
                ], 403);
            }

            $firstBooth = $booths->first();
            $floorPlanId = $firstBooth ? $firstBooth->floor_plan_id : null;
            $eventId = null;

            if ($floorPlanId) {
                $floorPlan = FloorPlan::find($floorPlanId);
                $eventId = $floorPlan ? $floorPlan->event_id : null;
            }

            // Get affiliate user ID from session (if customer came from affiliate link)
            $affiliateUserId = null;
            if (session()->has('affiliate_user_id') && session('affiliate_floor_plan_id') == $floorPlanId) {
                // Check if affiliate session is still valid (not expired)
                if (session()->has('affiliate_expires_at') && now()->lt(session('affiliate_expires_at'))) {
                    $affiliateUserId = session('affiliate_user_id');
                } else {
                    $affiliateUserId = null; // Session expired
                }
            }

            // Create booking with project/floor plan tracking
            // Calculate total amount from booths
            $totalAmount = $booths->sum('price');

            // Get default booking status (only if status column exists)
            $bookingStatus = null;
            if (Schema::hasColumn('book', 'status')) {
                try {
                    $defaultStatus = \App\Models\BookingStatusSetting::getDefault();
                    $bookingStatus = $defaultStatus ? $defaultStatus->status_code : Book::STATUS_PENDING;
                } catch (\Exception $e) {
                    $bookingStatus = Book::STATUS_PENDING;
                }
            }

            // Build booking data array
            $bookingData = [
                'event_id' => $eventId,
                'floor_plan_id' => $floorPlanId,
                'userid' => $userid,
                'type' => $bookingType,
                'clientid' => $clientID,
                'boothid' => json_encode($data['booth']),
                'date_book' => now(),
            ];

            // Add optional fields only if columns exist
            if ($affiliateUserId && Schema::hasColumn('book', 'affiliate_user_id')) {
                $bookingData['affiliate_user_id'] = $affiliateUserId;
            }

            if ($bookingStatus !== null && Schema::hasColumn('book', 'status')) {
                $bookingData['status'] = $bookingStatus;
            }

            if (Schema::hasColumn('book', 'total_amount')) {
                $bookingData['total_amount'] = $totalAmount;
            }

            if (Schema::hasColumn('book', 'paid_amount')) {
                $bookingData['paid_amount'] = 0;
            }

            if (Schema::hasColumn('book', 'balance_amount')) {
                $bookingData['balance_amount'] = $totalAmount;
            }

            $book = Book::create($bookingData);

            $bookID = $book->id;

            // Update booths with lock to prevent race conditions
            $updated = Booth::whereIn('id', $data['booth'])
                ->whereIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])
                ->lockForUpdate()
                ->update([
                    'status' => $boothStatus,
                    'client_id' => $clientID,
                    'userid' => $userid,
                    'bookid' => $bookID,
                    'booth_type_id' => $data['inputBoothType'] ?? null,
                    'asset_id' => $data['inputAsset'] ?? null,
                    'category_id' => $data['inputCategory'] ?? null,
                    'sub_category_id' => $data['inputSubCategory'] ?? null,
                ]);

            // Verify all booths were updated
            if ($updated !== count($data['booth'])) {
                DB::rollBack();

                return response()->json([
                    'status' => 403,
                    'message' => 'Some booths became unavailable during booking. Please try again.',
                ], 403);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Successful.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking API creation failed: '.$e->getMessage(), [
                'user_id' => auth()->user()->id ?? null,
                'data' => $data,
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error creating booking: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update booking action - Updates an existing booking
     * This matches the Yii actionUpbooking logic
     */
    public function upbooking(Request $request)
    {
        $data = $request->input('data');

        if (! isset($data)) {
            return response()->json([
                'status' => 403,
                'message' => 'Please Check Data Before Submit',
            ], 403);
        }

        // Replace @rp4and with & (Yii code does this)
        $data = str_replace('@rp4and', '&', $data);
        $data = json_decode($data, true);

        if (! is_array($data)) {
            return response()->json([
                'status' => 403,
                'message' => 'Invalid data format',
            ], 403);
        }

        // Validate required fields
        $requiredFields = ['companyID', 'book', 'inputCpnName', 'inputPosition', 'inputName', 'inputPhone',
            'booth', 'inputBoothType', 'inputAsset', 'inputCategory', 'inputSubCategory'];
        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Please Check Data Before Submit',
                ], 403);
            }
        }

        // Check category limits
        if ($this->isLimitCategory($data['inputCategory'], count($data['booth']), 1)) {
            return response()->json([
                'status' => 403,
                'message' => 'Limit Category Contact Admin',
            ], 403);
        }

        if ($this->isLimitCategory($data['inputSubCategory'], count($data['booth']), 2)) {
            return response()->json([
                'status' => 403,
                'message' => 'Limit Sub Category Contact Admin',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $userid = auth()->user()->id;

            // Find existing book
            $book = Book::where('clientid', $data['companyID'])->first();

            if (! $book) {
                DB::rollBack();

                return response()->json([
                    'status' => 403,
                    'message' => 'Booking not found',
                ], 403);
            }

            $getBoothDB = json_decode($book->boothid, true) ?? [];
            $getBoothRqs = $data['booth'];

            // Map booking type to status
            $bookingType = $data['book'] ?? 3;
            $boothStatus = ($bookingType == 2) ? Booth::STATUS_CONFIRMED : Booth::STATUS_RESERVED;

            // Find booths to release (in current but not in new)
            $boothsToRelease = array_diff($getBoothDB, $getBoothRqs);

            // Find booths to reserve (in new but not in current)
            $boothsToReserve = array_diff($getBoothRqs, $getBoothDB);

            // Check if new booths are available (with lock to prevent race conditions)
            if (! empty($boothsToReserve)) {
                $unavailableBooths = Booth::whereIn('id', $boothsToReserve)
                    ->whereNotIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])
                    ->where(function ($query) use ($book) {
                        $query->where('bookid', '!=', $book->id)
                            ->orWhereNull('bookid');
                    })
                    ->lockForUpdate()
                    ->get();

                if ($unavailableBooths->count() > 0) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 403,
                        'message' => 'Some booths are not available: '.$unavailableBooths->pluck('booth_number')->implode(', '),
                    ], 403);
                }
            }

            // Release old booths - but NOT if they are PAID
            if (! empty($boothsToRelease)) {
                $boothsToReleaseModels = Booth::whereIn('id', $boothsToRelease)->lockForUpdate()->get();
                $paidBooths = [];

                foreach ($boothsToReleaseModels as $booth) {
                    if ($booth->status === Booth::STATUS_PAID) {
                        $paidBooths[] = $booth->booth_number;
                        // Keep paid booths as-is, just remove booking reference
                        $booth->update([
                            'bookid' => null,
                        ]);
                    } else {
                        // Only release non-paid booths
                        $booth->update([
                            'status' => Booth::STATUS_AVAILABLE,
                            'client_id' => null,
                            'userid' => null,
                            'bookid' => null,
                        ]);
                    }
                }

                if (! empty($paidBooths)) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 403,
                        'message' => 'Cannot remove paid booths from booking: '.implode(', ', $paidBooths).'. Please refund payment first.',
                    ], 403);
                }
            }

            // Reserve new booths - use lock to prevent race conditions
            if (! empty($boothsToReserve)) {
                $updated = Booth::whereIn('id', $boothsToReserve)
                    ->whereIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])
                    ->lockForUpdate()
                    ->update([
                        'status' => $boothStatus,
                        'client_id' => $data['companyID'],
                        'userid' => $userid,
                        'bookid' => $book->id,
                        'booth_type_id' => $data['inputBoothType'] ?? null,
                        'asset_id' => $data['inputAsset'] ?? null,
                        'category_id' => $data['inputCategory'] ?? null,
                        'sub_category_id' => $data['inputSubCategory'] ?? null,
                    ]);

                // Verify all booths were updated
                if ($updated !== count($boothsToReserve)) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 403,
                        'message' => 'Some booths became unavailable during update. Please try again.',
                    ], 403);
                }
            }

            // Update existing booths with new client if client changed
            $boothsToKeep = array_intersect($getBoothDB, $getBoothRqs);
            if (! empty($boothsToKeep) && $book->clientid != $data['companyID']) {
                Booth::whereIn('id', $boothsToKeep)
                    ->where('status', '!=', Booth::STATUS_PAID) // Don't update paid booths
                    ->update([
                        'client_id' => $data['companyID'],
                        'booth_type_id' => $data['inputBoothType'] ?? null,
                        'asset_id' => $data['inputAsset'] ?? null,
                        'category_id' => $data['inputCategory'] ?? null,
                        'sub_category_id' => $data['inputSubCategory'] ?? null,
                    ]);
            }

            // Update existing booths status if booking type changed (but not if PAID)
            if (! empty($boothsToKeep)) {
                Booth::whereIn('id', $boothsToKeep)
                    ->where('status', '!=', Booth::STATUS_PAID)
                    ->update([
                        'status' => $boothStatus,
                    ]);
            }

            // Update book
            $book->boothid = json_encode($getBoothRqs);
            $book->type = $bookingType;
            $book->save();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Successful.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking API update failed: '.$e->getMessage(), [
                'user_id' => auth()->user()->id ?? null,
                'data' => $data,
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error updating booking: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client info for a booth (info action)
     */
    public function info($id)
    {
        $booth = Booth::findOrFail($id);
        $client = $booth->client;

        if (! $client) {
            return response()->json([]);
        }

        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
            'company' => $client->company,
            'position' => $client->position,
            'phone_number' => $client->phone_number,
        ]);
    }

    /**
     * Check if category limit is exceeded
     * This matches the Yii isLimitCat method
     *
     * @param  int  $categoryId  Category ID
     * @param  int  $boothCount  Number of booths being added
     * @param  int  $type  1 for category, 2 for sub-category
     * @return bool True if limit exceeded
     */
    private function isLimitCategory($categoryId, $boothCount, $type)
    {
        $category = Category::find($categoryId);

        if (! $category || ! $category->limit) {
            return false; // No limit set
        }

        if ($type == 2) {
            // Sub-category limit check
            $countBoothSub = Booth::where('sub_category_id', $categoryId)->count() + $boothCount;

            return $countBoothSub > $category->limit;
        } else {
            // Category limit check
            $countBoothCat = Booth::where('category_id', $categoryId)->count() + $boothCount;

            return $countBoothCat > $category->limit;
        }
    }

    /**
     * Batch-load booths for a collection of books to avoid N+1 queries.
     * Returns array keyed by book id => collection of Booth models.
     */
    private function loadBoothsForBooks($books): array
    {
        $boothsByBookId = [];
        $allBoothIds = $books->flatMap(function ($book) {
            $ids = json_decode($book->boothid, true) ?? [];

            return is_array($ids) ? $ids : [];
        })->unique()->filter()->values()->toArray();

        if (empty($allBoothIds)) {
            foreach ($books as $book) {
                $boothsByBookId[$book->id] = collect([]);
            }

            return $boothsByBookId;
        }

        $booths = Booth::whereIn('id', $allBoothIds)->orderBy('booth_number')->get()->keyBy('id');

        foreach ($books as $book) {
            $ids = json_decode($book->boothid, true) ?? [];
            $ids = is_array($ids) ? $ids : [];
            $boothsByBookId[$book->id] = collect($ids)->map(fn ($id) => $booths->get($id))->filter()->values();
        }

        return $boothsByBookId;
    }

    /**
     * Whether non-admin users are restricted to seeing only their own bookings (public view setting).
     */
    private function restrictToOwnBookings(): bool
    {
        if (! auth()->check()) {
            return false;
        }
        if (auth()->user()->isAdmin()) {
            return false;
        }

        return (bool) Setting::getValue('public_view_restrict_crud_to_own_booking', true);
    }

    /**
     * Whether the current user can manage this booking (edit/update/delete).
     * Admins and users with "owner" role can manage all; when "restrict to own" is on, others can only manage bookings they created.
     */
    private function canManageBooking(Book $book): bool
    {
        return auth()->check() && $book->canBeManagedBy(auth()->user());
    }
}
