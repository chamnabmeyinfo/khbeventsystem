<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ACCOUNT_KIND_REAL = 'real';

    public const ACCOUNT_KIND_DEMO = 'demo';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'type',
        'status',
        'account_kind',
        'role_id',
        'avatar',
        'cover_image',
        'last_login',
        'create_time',
        'update_time',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            // Note: last_login cast removed as column may not exist in all database versions
        ];
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Get the name of the "remember me" token column.
     * Override to return null to disable remember token functionality
     * (the remember_token column doesn't exist in the database)
     *
     * @return string|null
     */
    public function getRememberTokenName()
    {
        return null;
    }

    /**
     * Get the remember token value.
     * Override to return null since remember_token column doesn't exist
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return null;
    }

    /**
     * Set the remember token value.
     * Override to do nothing since remember_token column doesn't exist
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // Do nothing - column doesn't exist in database
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        $type = $this->type ?? $this->attributes['type'] ?? null;

        return $type === '1' || $type === 1;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        $status = $this->status ?? $this->attributes['status'] ?? null;

        if (! ($status === '1' || $status === 1)) {
            return false;
        }

        // Also check if linked employee is inactive, terminated, or suspended
        if ($this->relationLoaded('employee')) {
            if ($this->employee && in_array(strtolower($this->employee->status ?? ''), ['inactive', 'terminated', 'suspended'])) {
                return false;
            }
        } else {
            $employee = $this->employee()->first();
            if ($employee && in_array(strtolower($employee->status ?? ''), ['inactive', 'terminated', 'suspended'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user or linked employee is terminated
     */
    public function isTerminated(): bool
    {
        if ($this->relationLoaded('employee')) {
            if ($this->employee && strtolower($this->employee->status ?? '') === 'terminated') {
                return true;
            }
        } else {
            $employee = $this->employee()->first();
            if ($employee && strtolower($employee->status ?? '') === 'terminated') {
                return true;
            }
        }

        return false;
    }

    /**
     * Demo / sandbox login (training, QA, seeded demo data).
     */
    public function isDemoAccount(): bool
    {
        $kind = $this->account_kind ?? $this->attributes['account_kind'] ?? self::ACCOUNT_KIND_REAL;

        return $kind === self::ACCOUNT_KIND_DEMO;
    }

    /**
     * Production staff login (default when account_kind is unset or real).
     */
    public function isRealStaffAccount(): bool
    {
        return ! $this->isDemoAccount();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRealStaff($query)
    {
        return $query->where(function ($q) {
            $q->where('account_kind', self::ACCOUNT_KIND_REAL)
                ->orWhereNull('account_kind');
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDemoAccounts($query)
    {
        return $query->where('account_kind', self::ACCOUNT_KIND_DEMO);
    }

    /**
     * Get booths owned by this user
     */
    public function booths()
    {
        return $this->hasMany(Booth::class, 'userid');
    }

    /**
     * Get bookings made by this user
     */
    public function books()
    {
        return $this->hasMany(Book::class, 'userid');
    }

    /**
     * Get affiliate bookings (bookings where this user's link was used)
     */
    public function affiliateBookings()
    {
        return $this->hasMany(Book::class, 'affiliate_user_id');
    }

    /**
     * Get affiliate statistics
     */
    public function getAffiliateStats($floorPlanId = null, $dateFrom = null, $dateTo = null)
    {
        $query = $this->affiliateBookings();

        if ($floorPlanId) {
            $query->where('floor_plan_id', $floorPlanId);
        }

        if ($dateFrom) {
            $query->where('date_book', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date_book', '<=', $dateTo.' 23:59:59');
        }

        $bookings = $query->get();

        return [
            'total_bookings' => $bookings->count(),
            'total_revenue' => $bookings->sum(function ($booking) {
                $booths = $booking->booths();

                return $booths->sum('price') ?? 0;
            }),
            'unique_clients' => $bookings->pluck('clientid')->unique()->count(),
            'unique_floor_plans' => $bookings->pluck('floor_plan_id')->unique()->count(),
        ];
    }

    /**
     * Get the role for this user
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the employee record for this user
     */
    public function employee()
    {
        return $this->hasOne(\App\Models\HR\Employee::class, 'user_id');
    }

    /**
     * Get user display name (full employee name if available, otherwise username)
     */
    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->attributes['name'])) {
            return $this->attributes['name'];
        }

        if ($this->relationLoaded('employee') && $this->employee) {
            $fullName = trim(($this->employee->first_name ?? '') . ' ' . ($this->employee->last_name ?? ''));
            if (! empty($fullName)) {
                return $fullName;
            }
        }

        return $this->username ?? ('User #' . $this->id);
    }

    /**
     * Check if user has a specific permission
     * Optimized to load role and permissions efficiently
     */
    public function hasPermission($permissionSlug): bool
    {
        // Inactive or terminated staff have NO permissions anywhere
        if (! $this->isActive()) {
            return false;
        }

        // Admin always has all permissions
        if ($this->isAdmin()) {
            return true;
        }

        // Load role with permissions if not already loaded (prevents N+1 queries)
        if (! $this->relationLoaded('role')) {
            $this->load('role.permissions');
        }

        // Check if user's role has the permission
        if ($this->role) {
            return $this->role->hasPermission($permissionSlug);
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (! $this->hasPermission($slug)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for this user (including inactive - for free assignment)
     */
    public function getPermissions()
    {
        if ($this->isAdmin()) {
            return Permission::get(); // Return all permissions for admin
        }

        if ($this->role) {
            return $this->role->permissions()->get(); // Return all assigned permissions (active and inactive)
        }

        return collect([]);
    }
}
