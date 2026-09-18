<?php

namespace App\Http\Controllers;

use App\Models\HR\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        // If AJAX request for lazy loading
        if (($request->ajax() || $request->wantsJson() || $request->hasHeader('X-Requested-With')) && $request->has('page')) {
            return $this->lazyLoad($request);
        }

        $query = User::with(['role', 'role.permissions']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhereHas('role', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->filled('account_kind') && Schema::hasColumn('user', 'account_kind')) {
            if ($request->account_kind === User::ACCOUNT_KIND_REAL) {
                $query->realStaff();
            } elseif ($request->account_kind === User::ACCOUNT_KIND_DEMO) {
                $query->demoAccounts();
            }
        }

        // Filter by role (only if column exists)
        if ($request->filled('role_id') && \Illuminate\Support\Facades\Schema::hasColumn('user', 'role_id')) {
            if ($request->role_id == '0') {
                $query->whereNull('role_id');
            } else {
                $query->where('role_id', $request->role_id);
            }
        }

        // Get initial 20 records for lazy loading
        $users = $query->orderBy('username')->limit(20)->get();
        $total = $query->count();

        $roles = Role::where('is_active', true)->orderBy('name')->get();

        return view('users.index', compact('users', 'total', 'roles'));
    }

    /**
     * Lazy load users (AJAX endpoint)
     */
    public function lazyLoad(Request $request)
    {
        // Use exact same query structure as index method
        $query = User::with(['role', 'role.permissions']);

        // Search functionality (exact same as index)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhereHas('role', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by type (exact same as index)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status (exact same as index)
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->filled('account_kind') && Schema::hasColumn('user', 'account_kind')) {
            if ($request->account_kind === User::ACCOUNT_KIND_REAL) {
                $query->realStaff();
            } elseif ($request->account_kind === User::ACCOUNT_KIND_DEMO) {
                $query->demoAccounts();
            }
        }

        // Filter by role (exact same as index)
        if ($request->filled('role_id') && \Illuminate\Support\Facades\Schema::hasColumn('user', 'role_id')) {
            if ($request->role_id == '0') {
                $query->whereNull('role_id');
            } else {
                $query->where('role_id', $request->role_id);
            }
        }

        // Use same ordering and limit as initial load
        $page = $request->input('page', 1);
        $perPage = 20; // Same as initial load limit(20)
        $offset = ($page - 1) * $perPage;

        // Get total before pagination
        $total = $query->count();

        // Use exact same ordering as index method
        $users = $query->orderBy('username')->offset($offset)->limit($perPage)->get();
        $hasMore = ($offset + $users->count()) < $total;

        $html = '';
        foreach ($users as $user) {
            // Ensure relationships are loaded (same as initial load)
            if (! $user->relationLoaded('role')) {
                $user->load('role', 'role.permissions');
            }

            // Table row HTML - partial will calculate everything internally to match main view exactly
            $html .= view('users.partials.table-row', compact('user'))->render();
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'hasMore' => $hasMore,
            'total' => $total,
            'loaded' => $offset + $users->count(),
            'page' => $page,
            'perPage' => $perPage,
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:45|unique:user,username',
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/'],
            'type' => 'required|integer|in:1,2',
            'status' => 'required|integer|in:0,1',
            'role_id' => 'nullable|exists:roles,id',
            'account_kind' => 'nullable|in:'.User::ACCOUNT_KIND_REAL.','.User::ACCOUNT_KIND_DEMO,
        ], [
            'password.regex' => 'The password must be at least 8 characters and contain both letters and numbers.',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        if (! Schema::hasColumn('user', 'account_kind')) {
            unset($validated['account_kind']);
        } else {
            $validated['account_kind'] = $validated['account_kind'] ?? User::ACCOUNT_KIND_REAL;
        }

        $user = User::create($validated);

        $this->syncLinkedEmployeesAccountKind($user);

        // Return JSON if request expects JSON (for AJAX/modal requests)
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'type' => $user->type,
                    'status' => $user->status,
                    'role_id' => $user->role_id,
                    'account_kind' => Schema::hasColumn('user', 'account_kind') ? ($user->account_kind ?? User::ACCOUNT_KIND_REAL) : null,
                ],
            ], 200);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['role', 'role.permissions']);

        // Calculate affiliate/commission statistics
        $affiliateStats = $this->calculateAffiliateStats($user);

        return view('users.show', compact('user', 'affiliateStats'));
    }

    /**
     * Calculate affiliate statistics for a user
     */
    private function calculateAffiliateStats(User $user)
    {
        // Get affiliate bookings (bookings where this user is the affiliate)
        $affiliateBookings = \App\Models\Book::where('affiliate_user_id', $user->id)->get();

        // Calculate total revenue from affiliate bookings
        $totalRevenue = $affiliateBookings->sum(function ($booking) {
            $booths = $booking->booths();

            return $booths->sum('price') ?? 0;
        });

        // Calculate statistics
        $stats = [
            'total_bookings' => $affiliateBookings->count(),
            'total_revenue' => $totalRevenue,
            'unique_clients' => $affiliateBookings->pluck('clientid')->unique()->count(),
            'unique_floor_plans' => $affiliateBookings->pluck('floor_plan_id')->unique()->count(),
            'avg_booking_value' => $affiliateBookings->count() > 0 ? round($totalRevenue / $affiliateBookings->count(), 2) : 0,
            'last_booking_at' => $affiliateBookings->max('date_book'),
            'first_booking_at' => $affiliateBookings->min('date_book'),
            'total_clicks' => \App\Models\AffiliateClick::where('affiliate_user_id', $user->id)->count(),
            'conversion_rate' => $affiliateBookings->count() > 0 && \App\Models\AffiliateClick::where('affiliate_user_id', $user->id)->count() > 0
                ? round(($affiliateBookings->count() / \App\Models\AffiliateClick::where('affiliate_user_id', $user->id)->count()) * 100, 2)
                : 0,
        ];

        // Get bookings by month (last 6 months)
        $bookingsByMonth = \App\Models\Book::where('affiliate_user_id', $user->id)
            ->where('date_book', '>=', now()->subMonths(6))
            ->select(DB::raw('DATE_FORMAT(date_book, "%Y-%m") as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $stats['bookings_by_month'] = $bookingsByMonth;

        // Get recent bookings (last 5)
        $stats['recent_bookings'] = $affiliateBookings->sortByDesc('date_book')->take(5);

        return $stats;
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = Role::where('is_active', true)->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user
     * Matches Yii actionUpdate - only updates type and status
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'type' => 'required|integer|in:1,2',
            'status' => 'required|integer|in:0,1',
            'role_id' => 'nullable|exists:roles,id',
            'account_kind' => 'nullable|in:'.User::ACCOUNT_KIND_REAL.','.User::ACCOUNT_KIND_DEMO,
        ]);

        if (! Schema::hasColumn('user', 'account_kind')) {
            unset($validated['account_kind']);
        } else {
            $validated['account_kind'] = $validated['account_kind'] ?? $user->account_kind ?? User::ACCOUNT_KIND_REAL;
        }

        $user->update($validated);

        // Synchronize linked employee status if user status changed
        if (isset($validated['status'])) {
            $linkedEmployee = $user->employee;
            if ($linkedEmployee) {
                if ($validated['status'] == 0 && in_array($linkedEmployee->status, ['active', 'on-leave'])) {
                    $linkedEmployee->update(['status' => 'inactive']);
                } elseif ($validated['status'] == 1 && $linkedEmployee->status === 'inactive') {
                    $linkedEmployee->update(['status' => 'active']);
                }
            }
        }

        $this->syncLinkedEmployeesAccountKind($user->fresh());

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Update user password
     * Matches Yii actionUpdate_pass
     */
    public function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/'],
        ], [
            'password.regex' => 'The password must be at least 8 characters and contain both letters and numbers.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.show', $user)
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Toggle user status (AJAX)
     * Matches Yii actionStatus
     */
    public function status($id, Request $request)
    {
        if (! $request->has('status')) {
            return response()->json([
                'status' => 400,
                'message' => 'Status parameter is required',
            ], 400);
        }

        $user = User::findOrFail($id);
        $status = $request->input('status');

        if ($status === 'true' || $status === true || $status === '1' || $status === 1) {
            $user->status = 1;
        } else {
            $user->status = 0;
        }

        $user->save();

        // Synchronize linked employee status
        $linkedEmployee = $user->employee;
        if ($linkedEmployee) {
            if ($user->status == 0 && in_array($linkedEmployee->status, ['active', 'on-leave'])) {
                $linkedEmployee->update(['status' => 'inactive']);
            } elseif ($user->status == 1 && $linkedEmployee->status === 'inactive') {
                $linkedEmployee->update(['status' => 'active']);
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'User status updated successfully.',
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Update cover image position
     */
    /**
     * Keep HR employee rows aligned with login account kind when linked.
     */
    private function syncLinkedEmployeesAccountKind(User $user): void
    {
        if (! Schema::hasColumn('employees', 'account_kind') || ! Schema::hasColumn('user', 'account_kind')) {
            return;
        }

        $kind = $user->account_kind ?? User::ACCOUNT_KIND_REAL;
        Employee::where('user_id', $user->id)->update(['account_kind' => $kind]);
    }

    public function updateCoverPosition(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'x' => 'required|numeric|min:0|max:100',
                'y' => 'required|numeric|min:0|max:100',
                'position' => 'nullable|string|max:50',
            ]);

            // Store position as "x% y%" format
            $position = $validated['position'] ?? ($validated['x'].'% '.$validated['y'].'%');

            // Check if cover_position column exists, if not store in settings
            if (Schema::hasColumn('user', 'cover_position')) {
                $user->cover_position = $position;
                $user->save();
            } else {
                // Store in settings table as fallback
                \App\Models\Setting::setValue(
                    'user_'.$id.'_cover_position',
                    $position,
                    'string',
                    'Cover image position for user '.$id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Cover position updated successfully.',
                'position' => $position,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating cover position: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating cover position: '.$e->getMessage(),
            ], 500);
        }
    }
}
