<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'client_id',
        'amount',
        'cash_amount',
        'product_amount',
        'product_quantity',
        'product_unit_price',
        'product_details',
        'payment_method',
        'cash_payment_method',
        'status',
        'transaction_id',
        'notes',
        'paid_at',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'product_amount' => 'decimal:2',
        'product_quantity' => 'decimal:2',
        'product_unit_price' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_REFUNDED = 'refunded';

    const METHOD_CASH = 'cash';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_ONLINE = 'online';

    const METHOD_CHECK = 'check';

    const METHOD_PRODUCT_EXCHANGE = 'product_exchange';

    const METHOD_SPLIT = 'split';

    public function booking()
    {
        return $this->belongsTo(Book::class, 'booking_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isSplit(): bool
    {
        return $this->payment_method === self::METHOD_SPLIT;
    }

    public function isProductExchange(): bool
    {
        return $this->payment_method === self::METHOD_PRODUCT_EXCHANGE;
    }

    public function hasProductDeduction(): bool
    {
        return ((float) ($this->product_amount ?? 0) > 0) || $this->isProductExchange() || $this->isSplit();
    }

    /**
     * Get clean formatted product quantity (e.g. 10 instead of 10.00, or 10.5)
     */
    public function getProductQuantityFormattedAttribute(): ?string
    {
        if ($this->product_quantity === null || $this->product_quantity === '') {
            return null;
        }
        $val = (float) $this->product_quantity;
        return (floor($val) == $val) ? (string) (int) $val : rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
    }

    /**
     * Get human-readable method label with breakdown
     */
    public function getMethodLabelAttribute(): string
    {
        $qtyStr = $this->product_quantity_formatted;
        $unitPrice = (float) ($this->product_unit_price ?? 0);
        $prodMeta = '';
        if ($qtyStr && $unitPrice > 0) {
            $prodMeta = " [{$qtyStr} @ $" . number_format($unitPrice, 2) . ']';
        } elseif ($qtyStr) {
            $prodMeta = " [{$qtyStr} items]";
        }

        if ($this->isSplit()) {
            $cashPart = (float) ($this->cash_amount ?? 0);
            $prodPart = (float) ($this->product_amount ?? 0);
            $cashName = ucfirst(str_replace('_', ' ', $this->cash_payment_method ?? 'Cash'));

            return "Split ($" . number_format($cashPart, 2) . " {$cashName} + $" . number_format($prodPart, 2) . " Product{$prodMeta})";
        }

        if ($this->isProductExchange()) {
            $prodPart = (float) ($this->product_amount ?? $this->amount ?? 0);
            return "Product Exchange ($" . number_format($prodPart, 2) . "{$prodMeta})";
        }

        return match ($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'online' => 'Online Payment',
            'check' => 'Check',
            default => ucfirst(str_replace('_', ' ', $this->payment_method ?? 'N/A')),
        };
    }
}
