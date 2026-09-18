<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'position', 'manager', 'user']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by position
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employment type
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->filled('account_kind') && Schema::hasColumn('employees', 'account_kind')) {
            if ($request->account_kind === Employee::ACCOUNT_KIND_REAL) {
                $query->realStaff();
            } elseif ($request->account_kind === Employee::ACCOUNT_KIND_DEMO) {
                $query->demoStaff();
            }
        }

        $employees = $query->orderBy('first_name')->orderBy('last_name')->paginate(20)->withQueryString();

        $departments = Department::active()->orderBy('name')->get();
        $positions = Position::active()->orderBy('name')->get();

        return view('hr.employees.index', compact('employees', 'departments', 'positions'));
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        $departments = Department::active()->orderBy('name')->get();
        $positions = Position::active()->orderBy('name')->get();
        $managers = Employee::active()->orderBy('first_name')->orderBy('last_name')->get();
        $users = User::where('status', 1)->whereDoesntHave('employee')->orderBy('username')->get();

        return view('hr.employees.create', compact('departments', 'positions', 'managers', 'users'));
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:user,id|unique:employees,user_id',
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:100',
            'id_card_number' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'manager_id' => 'nullable|exists:employees,id',
            'employment_type' => 'required|in:full-time,part-time,contract,intern,temporary',
            'hire_date' => 'required|date',
            'probation_end_date' => 'nullable|date|after:hire_date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'account_kind' => 'nullable|in:'.Employee::ACCOUNT_KIND_REAL.','.Employee::ACCOUNT_KIND_DEMO,
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'active';

        $this->resolveEmployeeAccountKind($validated, $validated['user_id'] ?? null);

        // Generate employee code if not provided
        if (empty($validated['employee_code'])) {
            $validated['employee_code'] = $this->generateEmployeeCode();
        }

        $employee = Employee::create($validated);

        // Create initial leave balances for current year
        $this->initializeLeaveBalances($employee);

        // Create initial salary history entry
        if ($validated['salary'] ?? null) {
            $employee->salaryHistory()->create([
                'effective_date' => $validated['hire_date'],
                'salary' => $validated['salary'],
                'currency' => $validated['currency'] ?? 'USD',
                'reason' => 'Initial salary',
                'approved_by' => auth()->id(),
            ]);
        }

        return redirect()->route('hr.employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'position',
            'manager',
            'user',
            'directReports',
            'attendance' => function ($q) {
                $q->latest('date')->limit(30);
            },
            'leaveRequests' => function ($q) {
                $q->latest()->limit(10);
            },
            'performanceReviews' => function ($q) {
                $q->latest('review_date')->limit(5);
            },
            'documents',
            'training' => function ($q) {
                $q->latest()->limit(10);
            },
            'salaryHistory' => function ($q) {
                $q->latest('effective_date')->limit(10);
            },
        ]);

        // Get statistics
        $stats = [
            'total_attendance_days' => $employee->attendance()->where('status', 'present')->count(),
            'total_leave_days' => $employee->leaveRequests()->where('status', 'approved')->sum('total_days'),
            'pending_leave_requests' => $employee->leaveRequests()->where('status', 'pending')->count(),
            'performance_reviews_count' => $employee->performanceReviews()->count(),
            'average_rating' => $employee->performanceReviews()->avg('overall_rating'),
        ];

        return view('hr.employees.show', compact('employee', 'stats'));
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        $employee->loadMissing('user');
        $departments = Department::active()->orderBy('name')->get();
        $positions = Position::active()->orderBy('name')->get();
        $managers = Employee::active()->where('id', '!=', $employee->id)->orderBy('first_name')->orderBy('last_name')->get();
        $users = User::where('status', 1)
            ->where(function ($q) use ($employee) {
                $q->whereDoesntHave('employee')
                    ->orWhereHas('employee', function ($q2) use ($employee) {
                        $q2->where('id', $employee->id);
                    });
            })
            ->orderBy('username')
            ->get();

        return view('hr.employees.edit', compact('employee', 'departments', 'positions', 'managers', 'users'));
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:user,id|unique:employees,user_id,'.$employee->id,
            'employee_code' => 'required|string|max:50|unique:employees,employee_code,'.$employee->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:employees,email,'.$employee->id,
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:100',
            'id_card_number' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'manager_id' => 'nullable|exists:employees,id',
            'employment_type' => 'required|in:full-time,part-time,contract,intern,temporary',
            'hire_date' => 'required|date',
            'probation_end_date' => 'nullable|date|after:hire_date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'termination_date' => 'nullable|date',
            'termination_reason' => 'nullable|string',
            'status' => 'required|in:active,inactive,terminated,on-leave,suspended',
            'salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'account_kind' => 'nullable|in:'.Employee::ACCOUNT_KIND_REAL.','.Employee::ACCOUNT_KIND_DEMO,
        ]);

        $this->resolveEmployeeAccountKind($validated, $validated['user_id'] ?? null);

        // If salary changed, create history entry
        if (($validated['salary'] ?? null) && $validated['salary'] != $employee->salary) {
            $employee->salaryHistory()->create([
                'effective_date' => now(),
                'salary' => $validated['salary'],
                'currency' => $validated['currency'] ?? $employee->currency ?? 'USD',
                'reason' => 'Salary update',
                'approved_by' => auth()->id(),
            ]);
        }

        $employee->update($validated);

        // Synchronize linked user account status
        $linkedUserId = $validated['user_id'] ?? $employee->user_id;
        if ($linkedUserId) {
            $userStatus = ($validated['status'] === 'active') ? 1 : 0;
            User::where('id', $linkedUserId)->update(['status' => $userStatus]);
        }

        return redirect()->route('hr.employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        // Don't allow deletion if employee has active records
        if ($employee->attendance()->count() > 0 || $employee->leaveRequests()->count() > 0) {
            return redirect()->route('hr.employees.show', $employee)
                ->with('error', 'Cannot delete employee with existing records. Please terminate instead.');
        }

        if ($employee->user_id) {
            User::where('id', $employee->user_id)->update(['status' => 0]);
        }

        $employee->delete();

        return redirect()->route('hr.employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * Duplicate an employee
     */
    public function duplicate(Employee $employee)
    {
        $newEmployee = $employee->replicate();
        $newEmployee->employee_code = $this->generateEmployeeCode();
        $newEmployee->first_name = $employee->first_name.' (Copy)';
        $newEmployee->email = null; // Clear email to avoid unique constraint
        $newEmployee->user_id = null; // Clear user link
        $newEmployee->status = 'inactive'; // Set as inactive by default
        $newEmployee->created_by = auth()->id();
        $newEmployee->save();

        return redirect()->route('hr.employees.edit', $newEmployee)
            ->with('success', 'Employee duplicated successfully. Please update the details.');
    }

    /**
     * Linked employees inherit the user login's account_kind; unlinked use the form (default real).
     */
    private function resolveEmployeeAccountKind(array &$validated, ?int $userId): void
    {
        if (! Schema::hasColumn('employees', 'account_kind')) {
            unset($validated['account_kind']);

            return;
        }

        if ($userId) {
            $login = User::find($userId);
            if ($login && Schema::hasColumn('user', 'account_kind')) {
                $validated['account_kind'] = $login->account_kind ?? User::ACCOUNT_KIND_REAL;
            } else {
                $validated['account_kind'] = $validated['account_kind'] ?? Employee::ACCOUNT_KIND_REAL;
            }
        } else {
            $validated['account_kind'] = $validated['account_kind'] ?? Employee::ACCOUNT_KIND_REAL;
        }
    }

    /**
     * Generate unique employee code
     */
    private function generateEmployeeCode()
    {
        $prefix = 'EMP';
        $year = date('Y');
        $lastEmployee = Employee::where('employee_code', 'like', "{$prefix}{$year}%")
            ->orderBy('employee_code', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Initialize leave balances for new employee
     */
    private function initializeLeaveBalances(Employee $employee)
    {
        $year = date('Y');
        $leaveTypes = \App\Models\HR\LeaveType::active()->get();

        foreach ($leaveTypes as $leaveType) {
            $allocated = $leaveType->max_days_per_year ?? 0;

            $employee->leaveBalances()->create([
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'allocated_days' => $allocated,
                'used_days' => 0,
                'remaining_days' => $allocated,
                'carried_forward_days' => 0,
            ]);
        }
    }
}
