<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
class Book extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'book';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'floor_plan_id',
        'clientid',
        'boothid',
        'date_book',
        'userid',
        'affiliate_user_id',
        'type',
        // Optional fields (only included if columns exist in database)
        'status',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_due_date',
        'notes',
    ];

    /**
     * Get fillable attributes that actually exist in the database
     */
    public function getFillableAttributes()
    {
        $fillable = $this->fillable;
        $existingColumns = [];

        foreach ($fillable as $column) {
            if (Schema::hasColumn($this->getTable(), $column)) {
                $existingColumns[] = $column;
            }
        }

        return $existingColumns;
    }

    protected $casts = [
        'date_book' => 'datetime',
        'payment_due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 1;

    const STATUS_CONFIRMED = 2;

    const STATUS_RESERVED = 3;

    const STATUS_PAID = 4;

    const STATUS_PARTIALLY_PAID = 5;

    const STATUS_CANCELLED = 6;

    /**
     * Whether the given user can manage this booking (edit/update/delete).
     * Admins and users with "owner" role can manage all; when "restrict to own" is on, others can only manage bookings they created.
     */
    public function canBeManagedBy($user): bool
    {
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }
        $roleSlug = $user->role ? strtolower($user->role->slug ?? '') : '';
        if ($roleSlug === 'owner') {
            return true;
        }
        if (! (bool) \App\Models\Setting::getValue('public_view_restrict_crud_to_own_booking', true)) {
            return true;
        }

        return (int) $this->userid === (int) $user->id;
    }

    /**
     * Get the client
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'clientid');
    }

    /**
     * Get the user who made the booking
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    /**
     * Get the affiliate user (sales person whose link was used)
     */
    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    /**
     * Get booths in this booking (boothid is stored as JSON string)
     */
    public function booths()
    {
        $boothIds = json_decode($this->boothid, true) ?? [];
        if (empty($boothIds)) {
            return collect([]);
        }

        return Booth::whereIn('id', $boothIds)->get();
    }

    /**
     * Get the floor plan this booking belongs to
     */
    public function floorPlan()
    {
        return $this->belongsTo(FloorPlan::class, 'floor_plan_id');
    }

    /**
     * Get the event/project this booking belongs to
     */
    public function event()
    {
        // Check if events table exists before trying to use relationship
        if (\Schema::hasTable('events') && $this->event_id) {
            try {
                return $this->belongsTo(Event::class, 'event_id');
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Get payments for this booking
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    /**
     * Get booking status setting
     */
    public function statusSetting()
    {
        return $this->belongsTo(BookingStatusSetting::class, 'status', 'status_code');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $statusSetting = $this->statusSetting;
        if ($statusSetting) {
            return $statusSetting->status_name;
        }

        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Calculate total amount from booths
     */
    public function calculateTotalAmount()
    {
        return $this->booths()->sum('price');
    }

    /**
     * Calculate paid amount from payments
     */
    public function calculatePaidAmount()
    {
        return $this->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');
    }

    /**
     * Calculate real money (cash/bank) paid amount
     */
    public function calculateCashPaidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(cash_amount, CASE WHEN payment_method != "product_exchange" THEN amount ELSE 0 END)'));
    }

    /**
     * Calculate product barter deduction paid amount
     */
    public function calculateProductPaidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(product_amount, CASE WHEN payment_method = "product_exchange" THEN amount ELSE 0 END)'));
    }

    /**
     * Calculate total product barter count/quantity exchanged
     */
    public function calculateProductCount(): float
    {
        return (float) $this->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('product_quantity');
    }

    /**
     * Update payment amounts
     */
    public function updatePaymentAmounts()
    {
        $this->total_amount = $this->calculateTotalAmount();
        $this->paid_amount = $this->calculatePaidAmount();
        $this->balance_amount = $this->total_amount - $this->paid_amount;

        // Auto-update status based on payment
        if ($this->balance_amount <= 0 && $this->total_amount > 0) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->paid_amount > 0 && $this->balance_amount > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }

        $this->save();
    }
}

