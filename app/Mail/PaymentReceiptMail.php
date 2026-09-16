<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $booking;
    public $client;
    public $booth;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->booking = $payment->booking;
        $this->client = $payment->client;
        $this->booth = $this->booking ? $this->booking->booth : null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $receiptNumber = 'RCP-' . str_pad($this->payment->id, 6, '0', STR_PAD_LEFT);
        
        return new Envelope(
            subject: "💳 Payment Receipt #{$receiptNumber} - KHB Events",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $receiptNumber = 'RCP-' . str_pad($this->payment->id, 6, '0', STR_PAD_LEFT);
        
    return new Content(
            view: 'emails.payment-receipt',
            with: [
                'payment' => $this->payment,
           'booking' => $this->booking,
                'client' => $this->client,
             'booth' => $this->booth,
                'receiptNumber' => $receiptNumber,
                'paymentDate' => $this->payment->paid_at ?? $this->payment->created_at,
                'amount' => $this->payment->amount,
                'paymentMethod' => $this->getPaymentMethodLabel($this->payment->payment_method),
                'cashAmount' => $this->payment->cash_amount,
                'productAmount' => $this->payment->product_amount,
                'productQuantity' => $this->payment->product_quantity,
                'productQuantityFormatted' => $this->payment->product_quantity_formatted,
                'productUnitPrice' => $this->payment->product_unit_price,
                'productDetails' => $this->payment->product_details,
                'isSplit' => method_exists($this->payment, 'isSplit') ? $this->payment->isSplit() : ($this->payment->payment_method === 'split'),
                'isProductExchange' => method_exists($this->payment, 'isProductExchange') ? $this->payment->isProductExchange() : ($this->payment->payment_method === 'product_exchange'),
                'transactionId' => $this->payment->transaction_id,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get human-readable payment method label
     */
    private function getPaymentMethodLabel($method): string
    {
        return match(strtolower($method ?? '')) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'aba' => 'ABA Pay',
            'wing' => 'Wing',
            'paypal' => 'PayPal',
            'product_exchange' => 'Product Exchange',
            'split' => 'Split (Money + Product Exchange)',
            default => ucfirst(str_replace('_', ' ', $method ?? 'Other')),
        };
    }
}
