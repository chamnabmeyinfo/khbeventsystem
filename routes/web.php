<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientProfileDashboardDemoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FloorPlanController;
use App\Http\Controllers\GitDeploymentController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LandingPageSectionTemplateController;
use App\Http\Controllers\LandingPagePublicController;
use App\Http\Controllers\LandingPageTrackingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->middleware('landing.gate')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public Routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->middleware('landing.gate');

// Landing Pages (Public)
Route::get('/l/{landingPage:slug}/thank-you', [LandingPagePublicController::class, 'thankYou'])->name('landing-pages.public.thank-you');
Route::get('/l/{landingPage:slug}', [LandingPagePublicController::class, 'show'])->name('landing-pages.public.show');
Route::post('/l/{landingPage:slug}/continue', [LandingPagePublicController::class, 'continue'])->name('landing-pages.public.continue');
Route::post('/l/{landingPage:slug}/track', [LandingPageTrackingController::class, 'track'])->name('landing-pages.track');
Route::post('/l/{landingPage:slug}/lead', [LandingPageTrackingController::class, 'lead'])
    ->middleware('throttle:40,1')
    ->name('landing-pages.lead');

// Client Portal (Public)
Route::prefix('client-portal')->name('client-portal.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\ClientPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\ClientPortalController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    Route::post('/logout', [\App\Http\Controllers\ClientPortalController::class, 'logout'])->name('logout');

    Route::middleware(['client.portal'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ClientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\ClientPortalController::class, 'profile'])->name('profile');
        Route::post('/profile', [\App\Http\Controllers\ClientPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/booking/{id}', [\App\Http\Controllers\ClientPortalController::class, 'booking'])->name('booking');
    });
});

// Public Routes (No Authentication Required)
Route::get('/floor-plans/{id}/public', [\App\Http\Controllers\BoothController::class, 'publicView'])->name('floor-plans.public');
Route::get('/floor-plans', [FloorPlanController::class, 'index'])->name('floor-plans.index');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/demo/client-profile-dashboard', ClientProfileDashboardDemoController::class)
        ->name('demo.client-profile-dashboard');

    // Floor Plans (Floor Plan Management - Create, Edit, Delete require auth)
    // IMPORTANT: Define specific routes (like /create) BEFORE parameterized routes to avoid route conflicts
    Route::get('/floor-plans/create', [FloorPlanController::class, 'create'])->name('floor-plans.create');
    Route::post('/floor-plans', [FloorPlanController::class, 'store'])->name('floor-plans.store');
    Route::get('/floor-plans/{floorPlan}/edit', [FloorPlanController::class, 'edit'])->name('floor-plans.edit');
    Route::put('/floor-plans/{floorPlan}', [FloorPlanController::class, 'update'])->name('floor-plans.update');
    Route::delete('/floor-plans/{floorPlan}', [FloorPlanController::class, 'destroy'])->name('floor-plans.destroy');
    Route::post('/floor-plans/{id}/set-default', [FloorPlanController::class, 'setDefault'])->name('floor-plans.set-default');
    Route::post('/floor-plans/{id}/duplicate', [FloorPlanController::class, 'duplicate'])->name('floor-plans.duplicate');
    Route::post('/floor-plans/{id}/affiliate-link', [FloorPlanController::class, 'generateAffiliateLink'])->name('floor-plans.generate-affiliate-link');

    // Zones - CRUD Operations
    Route::resource('zones', ZoneController::class);
});

// Public Floor Plan Show Route (must be after protected routes to avoid conflicts)
Route::get('/floor-plans/{floorPlan}', [FloorPlanController::class, 'show'])->name('floor-plans.show');

// Protected Routes (continued)
Route::middleware(['auth'])->group(function () {
    // Affiliate Management
    Route::get('/affiliates', [\App\Http\Controllers\AffiliateController::class, 'index'])->name('affiliates.index');
    Route::get('/affiliates/statistics/data', [\App\Http\Controllers\AffiliateController::class, 'statistics'])->name('affiliates.statistics');
    Route::get('/affiliates/export', [\App\Http\Controllers\AffiliateController::class, 'export'])->name('affiliates.export');

    // Affiliate Benefits Management (must come before /affiliates/{id} to avoid route conflict)
    Route::resource('affiliates/benefits', \App\Http\Controllers\AffiliateBenefitController::class)->names([
        'index' => 'affiliates.benefits.index',
        'create' => 'affiliates.benefits.create',
        'store' => 'affiliates.benefits.store',
        'show' => 'affiliates.benefits.show',
        'edit' => 'affiliates.benefits.edit',
        'update' => 'affiliates.benefits.update',
        'destroy' => 'affiliates.benefits.destroy',
    ]);
    Route::post('/affiliates/benefits/{id}/toggle-status', [\App\Http\Controllers\AffiliateBenefitController::class, 'toggleStatus'])->name('affiliates.benefits.toggle-status');

    // Affiliate Details (must come after benefits routes)
    Route::get('/affiliates/{id}', [\App\Http\Controllers\AffiliateController::class, 'show'])->name('affiliates.show');

    // Booths
    Route::resource('booths', BoothController::class);
    Route::get('/booths/canvas/data', [BoothController::class, 'canvasData'])->name('booths.canvas-data');
    // Specific route for JSON booth data (for AJAX requests)
    Route::get('/booths/{booth}/json', [BoothController::class, 'show'])->name('booths.show.json');
    Route::get('/my-booths', [BoothController::class, 'myBooths'])->name('booths.my-booths');
    Route::post('/booths/{id}/confirm', [BoothController::class, 'confirmReservation'])->name('booths.confirm');
    Route::post('/booths/{id}/clear', [BoothController::class, 'clearReservation'])->name('booths.clear');
    Route::post('/booths/{id}/paid', [BoothController::class, 'markPaid'])->name('booths.paid');
    Route::post('/booths/{id}/remove', [BoothController::class, 'removeBooth'])->name('booths.remove');
    Route::post('/booths/update-external-view', [BoothController::class, 'updateExternalView'])->name('booths.update-external-view');
    Route::post('/booths/{id}/save-position', [BoothController::class, 'savePosition'])->name('booths.save-position');
    Route::post('/booths/save-all-positions', [BoothController::class, 'saveAllPositions'])->name('booths.save-all-positions');
    Route::post('/booths/upload-floorplan', [BoothController::class, 'uploadFloorplan'])->name('booths.upload-floorplan');
    Route::post('/booths/remove-floorplan', [BoothController::class, 'removeFloorplan'])->name('booths.remove-floorplan');
    Route::get('/booths/check-duplicate/{boothNumber}', [BoothController::class, 'checkDuplicate'])->name('booths.check-duplicate');
    Route::get('/booths/zone-settings/{zoneName}', [BoothController::class, 'getZoneSettings'])->name('booths.get-zone-settings');
    Route::post('/booths/zone-settings/{zoneName}', [BoothController::class, 'saveZoneSettings'])->name('booths.save-zone-settings');
    Route::post('/booths/create-in-zone/{zoneName}', [BoothController::class, 'createBoothInZone'])->name('booths.create-in-zone');
    Route::post('/booths/delete-in-zone/{zoneName}', [BoothController::class, 'deleteBoothsInZone'])->name('booths.delete-in-zone');
    Route::post('/booths/delete-by-ids', [BoothController::class, 'deleteBoothsByIds'])->name('booths.delete-by-ids');
    Route::post('/booths/restore', [BoothController::class, 'restoreBooths'])->name('booths.restore');
    Route::post('/booths/check-bookings', [BoothController::class, 'checkBoothsBookings'])->name('booths.check-bookings');
    Route::post('/booths/book-booth', [BoothController::class, 'bookBooth'])->name('booths.book-booth');
    Route::post('/booths/canvas-text', [BoothController::class, 'saveCanvasText'])->name('booths.save-canvas-text');
    Route::post('/booths/{id}/upload-image', [BoothController::class, 'uploadBoothImage'])->name('booths.upload-image');

    // Booth Gallery Images (Multiple Images)
    Route::post('/booths/{id}/upload-gallery', [BoothController::class, 'uploadBoothGalleryImages'])->name('booths.upload-gallery');
    Route::get('/booths/{id}/images', [BoothController::class, 'getBoothImages'])->name('booths.get-images');
    Route::delete('/booths/{boothId}/images/{imageId}', [BoothController::class, 'deleteBoothImage'])->name('booths.delete-image');
    Route::post('/booths/{boothId}/images/{imageId}/set-primary', [BoothController::class, 'setPrimaryImage'])->name('booths.set-primary-image');
    Route::post('/booths/{boothId}/images/reorder', [BoothController::class, 'updateImageOrder'])->name('booths.reorder-images');

    // Clients
    // IMPORTANT: Define specific routes BEFORE resource routes to avoid route conflicts
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::post('/clients/remove-duplicates', [ClientController::class, 'removeDuplicates'])->name('clients.remove-duplicates');
    Route::resource('clients', ClientController::class);
    Route::post('/clients/{id}/cover-position', [ClientController::class, 'updateCoverPosition'])->name('clients.cover-position.update');

    // Export Routes
    Route::get('/export', [ExportController::class, 'index'])->name('export.index');
    Route::get('/export/booths', [ExportController::class, 'exportBooths'])->name('export.booths');
    Route::get('/export/clients', [ExportController::class, 'exportClients'])->name('export.clients');
    Route::get('/export/bookings', [ExportController::class, 'exportBookings'])->name('export.bookings');
    Route::get('/export/pdf', [ExportController::class, 'exportToPdf'])->name('export.pdf');
    Route::post('/export/import', [ExportController::class, 'import'])->name('export.import');

    // Books
    Route::resource('books', BookController::class);
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::get('/books/{book}/for-restore', [BookController::class, 'forRestore'])->name('books.for-restore');
    Route::post('/books/restore', [BookController::class, 'restore'])->name('books.restore');
    Route::post('/books/delete-all', [BookController::class, 'deleteAll'])->name('books.delete-all');
    Route::get('/books/get-booths', [BookController::class, 'getBooths'])->name('books.get-booths');
    Route::post('/books/booking', [BookController::class, 'booking'])->name('books.booking');
    Route::post('/books/upbooking', [BookController::class, 'upbooking'])->name('books.upbooking');
    Route::get('/books/info/{id}', [BookController::class, 'info'])->name('books.info');
    Route::post('/books/{book}/update-status', [BookController::class, 'updateStatus'])->name('books.update-status');
    Route::post('/books/{book}/reassign-user', [BookController::class, 'updateBookedBy'])->name('books.reassign-user');

    // Categories
    Route::resource('categories', CategoryController::class);
    Route::post('/categories/create-category', [CategoryController::class, 'createCategory'])->name('categories.create-category');
    Route::post('/categories/update-category', [CategoryController::class, 'updateCategory'])->name('categories.update-category');
    Route::delete('/categories/delete-category/{id}', [CategoryController::class, 'deleteCategory'])->name('categories.delete-category');
    Route::post('/categories/create-sub-category', [CategoryController::class, 'createSubCategory'])->name('categories.create-sub-category');
    Route::post('/categories/update-sub-category', [CategoryController::class, 'updateSubCategory'])->name('categories.update-sub-category');

    // Finance Dashboard
    Route::get('/finance/dashboard', [\App\Http\Controllers\FinanceController::class, 'dashboard'])->name('finance.dashboard');

    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/sales', [\App\Http\Controllers\ReportController::class, 'salesReport'])->name('sales');
        Route::get('/trends', [\App\Http\Controllers\ReportController::class, 'bookingTrends'])->name('trends');
        Route::get('/user-performance', [\App\Http\Controllers\ReportController::class, 'userPerformance'])->name('user-performance');
        Route::get('/revenue-chart', [\App\Http\Controllers\ReportController::class, 'revenueChart'])->name('revenue-chart');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('recent');
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/push-subscribe', [\App\Http\Controllers\NotificationController::class, 'pushSubscribe'])->name('push-subscribe');
        Route::post('/send-test-push', [\App\Http\Controllers\NotificationController::class, 'sendTestPush'])->name('send-test-push');
    });

    // Finance Module
    Route::prefix('finance')->name('finance.')->group(function () {
        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\PaymentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\PaymentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\PaymentController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\PaymentController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\PaymentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\PaymentController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\PaymentController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/invoice', [\App\Http\Controllers\PaymentController::class, 'invoice'])->name('invoice');
            Route::post('/{id}/refund', [\App\Http\Controllers\PaymentController::class, 'refund'])->name('refund');
            Route::post('/{id}/void', [\App\Http\Controllers\PaymentController::class, 'void'])->name('void');
        });

        // Finance Management
        Route::resource('costings', \App\Http\Controllers\Finance\CostingController::class);
        Route::resource('expenses', \App\Http\Controllers\Finance\ExpenseController::class);
        Route::resource('revenues', \App\Http\Controllers\Finance\RevenueController::class);
        Route::resource('categories', \App\Http\Controllers\Finance\FinanceCategoryController::class);

        // Booth Pricing routes
        Route::get('booth-pricing', [\App\Http\Controllers\Finance\BoothPricingController::class, 'index'])->name('booth-pricing.index');
        Route::get('booth-pricing/{id}/edit', [\App\Http\Controllers\Finance\BoothPricingController::class, 'edit'])->name('booth-pricing.edit');
        Route::put('booth-pricing/{id}', [\App\Http\Controllers\Finance\BoothPricingController::class, 'update'])->name('booth-pricing.update');
        Route::post('booth-pricing/bulk-update', [\App\Http\Controllers\Finance\BoothPricingController::class, 'bulkUpdate'])->name('booth-pricing.bulk-update');
        Route::get('booth-pricing/export', [\App\Http\Controllers\Finance\BoothPricingController::class, 'export'])->name('booth-pricing.export');
    });

    // Communications (staff chat / messages)
    Route::prefix('communications')->name('communications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CommunicationController::class, 'index'])->name('index');
        Route::get('/unread-count', [\App\Http\Controllers\CommunicationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/create', [\App\Http\Controllers\CommunicationController::class, 'create'])->name('create');
        Route::post('/send', [\App\Http\Controllers\CommunicationController::class, 'send'])->name('send');
        Route::get('/{id}', [\App\Http\Controllers\CommunicationController::class, 'show'])->name('show');
        Route::post('/announcement', [\App\Http\Controllers\CommunicationController::class, 'announcement'])->name('announcement');
    });

    // Global Search
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'search'])->name('search');

    // Activity Logs
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activityLog}', [\App\Http\Controllers\ActivityLogController::class, 'show'])->name('show');
        Route::get('/export/csv', [\App\Http\Controllers\ActivityLogController::class, 'export'])->name('export');
    });

    // Email Templates
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EmailTemplateController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\EmailTemplateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\EmailTemplateController::class, 'store'])->name('store');
        Route::get('/{emailTemplate}', [\App\Http\Controllers\EmailTemplateController::class, 'show'])->name('show');
        Route::get('/{emailTemplate}/preview', [\App\Http\Controllers\EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{emailTemplate}/send-test', [\App\Http\Controllers\EmailTemplateController::class, 'sendTest'])->name('send-test');
        Route::get('/{emailTemplate}/edit', [\App\Http\Controllers\EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{emailTemplate}', [\App\Http\Controllers\EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{emailTemplate}', [\App\Http\Controllers\EmailTemplateController::class, 'destroy'])->name('destroy');
    });

    // Bulk Operations
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::post('/booths/update', [\App\Http\Controllers\BulkOperationController::class, 'bulkUpdateBooths'])->name('booths.update');
        Route::post('/booths/delete', [\App\Http\Controllers\BulkOperationController::class, 'bulkDeleteBooths'])->name('booths.delete');
        Route::post('/clients/update', [\App\Http\Controllers\BulkOperationController::class, 'bulkUpdateClients'])->name('clients.update');
        Route::post('/clients/delete', [\App\Http\Controllers\BulkOperationController::class, 'bulkDeleteClients'])->name('clients.delete');
        Route::post('/clients/merge', [\App\Http\Controllers\BulkOperationController::class, 'bulkMergeClients'])->name('clients.merge');
    });

    // HR Routes
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('hr.dashboard');
        })->middleware('permission:hr.dashboard.view')->name('root');

        // HR Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\HR\HRDashboardController::class, 'index'])->name('dashboard')->middleware('permission:hr.dashboard.view');

        // Employees
        Route::resource('employees', \App\Http\Controllers\HR\EmployeeController::class)->middleware('permission:hr.employees.view');
        Route::post('/employees/{employee}/duplicate', [\App\Http\Controllers\HR\EmployeeController::class, 'duplicate'])->name('employees.duplicate')->middleware('permission:hr.employees.create');

        // Departments
        Route::resource('departments', \App\Http\Controllers\HR\DepartmentController::class)->middleware('permission:hr.departments.view');
        Route::post('/departments/{department}/duplicate', [\App\Http\Controllers\HR\DepartmentController::class, 'duplicate'])->name('departments.duplicate')->middleware('permission:hr.departments.create');

        // Positions
        Route::resource('positions', \App\Http\Controllers\HR\PositionController::class)->middleware('permission:hr.positions.view');
        Route::post('/positions/{position}/duplicate', [\App\Http\Controllers\HR\PositionController::class, 'duplicate'])->name('positions.duplicate')->middleware('permission:hr.positions.create');

        // Attendance
        Route::resource('attendance', \App\Http\Controllers\HR\AttendanceController::class)->middleware('permission:hr.attendance.view');
        Route::post('/attendance/{attendance}/approve', [\App\Http\Controllers\HR\AttendanceController::class, 'approve'])->name('attendance.approve')->middleware('permission:hr.attendance.approve');

        // Leave Requests
        Route::resource('leaves', \App\Http\Controllers\HR\LeaveController::class)->middleware('permission:hr.leaves.view');
        Route::post('/leaves/{leaveRequest}/approve', [\App\Http\Controllers\HR\LeaveController::class, 'approve'])->name('leaves.approve')->middleware('permission:hr.leaves.approve');
        Route::post('/leaves/{leaveRequest}/reject', [\App\Http\Controllers\HR\LeaveController::class, 'reject'])->name('leaves.reject')->middleware('permission:hr.leaves.approve');
        Route::post('/leaves/{leaveRequest}/cancel', [\App\Http\Controllers\HR\LeaveController::class, 'cancel'])->name('leaves.cancel')->middleware('permission:hr.leaves.manage');

        // Leave Calendar
        Route::get('/leave-calendar', [\App\Http\Controllers\HR\LeaveCalendarController::class, 'index'])->name('leave-calendar.index')->middleware('permission:hr.leaves.view');
        Route::get('/leave-calendar/data', [\App\Http\Controllers\HR\LeaveCalendarController::class, 'getCalendarData'])->name('leave-calendar.data')->middleware('permission:hr.leaves.view');

        // Leave Types
        Route::resource('leave-types', \App\Http\Controllers\HR\LeaveTypeController::class)->middleware('permission:hr.leaves.manage');

        // Performance Reviews
        Route::resource('performance', \App\Http\Controllers\HR\PerformanceReviewController::class)->middleware('permission:hr.performance.view');

        // Training
        Route::resource('training', \App\Http\Controllers\HR\TrainingController::class)->middleware('permission:hr.training.view');

        // Documents
        Route::resource('documents', \App\Http\Controllers\HR\DocumentController::class)->middleware('permission:hr.documents.view');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\HR\DocumentController::class, 'download'])->name('documents.download')->middleware('permission:hr.documents.view');

        // Salary History
        Route::resource('salary', \App\Http\Controllers\HR\SalaryHistoryController::class)->middleware('permission:hr.salary.view');
    });

    // Employee Self-Service Portal
    Route::prefix('employee-portal')->name('employee.')->middleware('auth')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\HR\EmployeePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\HR\EmployeePortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\HR\EmployeePortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/leaves', [\App\Http\Controllers\HR\EmployeePortalController::class, 'leaves'])->name('leaves');
        Route::post('/leaves/apply', [\App\Http\Controllers\HR\EmployeePortalController::class, 'applyLeave'])->name('leaves.apply');
        Route::get('/attendance', [\App\Http\Controllers\HR\EmployeePortalController::class, 'attendance'])->name('attendance');
        Route::get('/documents', [\App\Http\Controllers\HR\EmployeePortalController::class, 'documents'])->name('documents');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\HR\EmployeePortalController::class, 'downloadDocument'])->name('documents.download');
    });

    // Manager Dashboard
    Route::prefix('manager')->name('manager.')->middleware('auth')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\HR\ManagerDashboardController::class, 'index'])->name('dashboard');
        Route::post('/leaves/{leaveRequest}/approve', [\App\Http\Controllers\HR\ManagerDashboardController::class, 'approveLeave'])->name('leaves.approve');
        Route::post('/leaves/{leaveRequest}/reject', [\App\Http\Controllers\HR\ManagerDashboardController::class, 'rejectLeave'])->name('leaves.reject');
        Route::post('/attendance/{attendance}/approve', [\App\Http\Controllers\HR\ManagerDashboardController::class, 'approveAttendance'])->name('attendance.approve');
        Route::post('/leaves/bulk-approve', [\App\Http\Controllers\HR\ManagerDashboardController::class, 'bulkApproveLeaves'])->name('leaves.bulk-approve');
    });

    // Documentation & Changelog (all authenticated users)
    Route::get('/documentation', [VersionController::class, 'documentation'])->name('documentation.index');
    Route::get('/docs/{path?}', [VersionController::class, 'serveDocFile'])->name('docs.file')->where('path', '.*');

    // Admin Routes
    Route::middleware(['admin'])->group(function () {
        // Staff Management — roles & permissions hub (tabbed)
        Route::get('staff/access', [\App\Http\Controllers\StaffAccessController::class, 'index'])->name('staff.access');
        Route::resource('roles', \App\Http\Controllers\RoleController::class);
        Route::resource('permissions', \App\Http\Controllers\PermissionController::class);

        // Users
        Route::resource('users', UserController::class);
        Route::post('/users/{id}/status', [UserController::class, 'status'])->name('users.status');
        Route::post('/users/{id}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
        Route::post('/users/{id}/cover-position', [UserController::class, 'updateCoverPosition'])->name('users.cover-position.update');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::prefix('landing-pages')->name('landing-pages.')->group(function () {
            Route::get('/', [LandingPageController::class, 'index'])->name('index');
            Route::get('/create', [LandingPageController::class, 'create'])->name('create');
            Route::post('/', [LandingPageController::class, 'store'])->name('store');
            Route::get('/reporting', [LandingPageController::class, 'reportingIndex'])->name('reporting.index');
            Route::get('/reporting/create', [LandingPageController::class, 'reportingCreate'])->name('reporting.create');
            Route::post('/reporting', [LandingPageController::class, 'reportingStore'])->name('reporting.store');
            Route::get('/reporting/{lead}', [LandingPageController::class, 'reportingShow'])
                ->whereNumber('lead')
                ->name('reporting.show');
            Route::get('/reporting/{lead}/edit', [LandingPageController::class, 'reportingEdit'])
                ->whereNumber('lead')
                ->name('reporting.edit');
            Route::put('/reporting/{lead}', [LandingPageController::class, 'reportingUpdate'])
                ->whereNumber('lead')
                ->name('reporting.update');
            Route::delete('/reporting/{lead}', [LandingPageController::class, 'reportingDestroy'])
                ->whereNumber('lead')
                ->name('reporting.destroy');
            Route::get('/analytics', [LandingPageController::class, 'analyticsIndex'])->name('analytics.index');
            Route::post('/analytics/clear', [LandingPageController::class, 'analyticsClear'])
                ->middleware('throttle:30,1')
                ->name('analytics.clear');
            Route::get('/section-templates', [LandingPageSectionTemplateController::class, 'index'])->name('section-templates.index');
            Route::get('/section-templates/create', [LandingPageSectionTemplateController::class, 'create'])->name('section-templates.create');
            Route::post('/section-templates', [LandingPageSectionTemplateController::class, 'store'])->name('section-templates.store');
            Route::get('/section-templates/{section_template}/edit', [LandingPageSectionTemplateController::class, 'edit'])->name('section-templates.edit');
            Route::put('/section-templates/{section_template}', [LandingPageSectionTemplateController::class, 'update'])->name('section-templates.update');
            Route::delete('/section-templates/{section_template}', [LandingPageSectionTemplateController::class, 'destroy'])->name('section-templates.destroy');
            Route::post('/{landingPage:slug}/apply-section-template', [LandingPageController::class, 'applySectionTemplate'])
                ->name('apply-section-template');
            Route::get('/{landingPage:slug}/edit', [LandingPageController::class, 'edit'])->name('edit');
            Route::get('/{landingPage:slug}/translation-center', [LandingPageController::class, 'translationCenter'])->name('translation-center');
            Route::put('/{landingPage:slug}/translation-center', [LandingPageController::class, 'updateTranslationCenter'])->name('translation-center.update');
            Route::put('/{landingPage:slug}', [LandingPageController::class, 'update'])->name('update');
            Route::delete('/{landingPage:slug}', [LandingPageController::class, 'destroy'])->name('destroy');
            Route::get('/{landingPage:slug}/preview', [LandingPageController::class, 'preview'])->name('preview');
            Route::post('/{landingPage:slug}/publish', [LandingPageController::class, 'publish'])->name('publish');
            Route::post('/{landingPage:slug}/unpublish', [LandingPageController::class, 'unpublish'])->name('unpublish');
            Route::post('/{landingPage:slug}/set-active', [LandingPageController::class, 'setActive'])->name('set-active');
            Route::post('/{landingPage:slug}/visual-inline', [LandingPageController::class, 'updateVisualInline'])->name('visual-inline');
            Route::post('/{landingPage:slug}/translate-from-english', [LandingPageController::class, 'translateFromEnglish'])->name('translate-from-english');
            Route::post('/parse-agenda-days', [LandingPageController::class, 'parseAgendaDays'])->name('parse-agenda-days');
            Route::get('/{landingPage:slug}/analytics', [LandingPageTrackingController::class, 'analytics'])->name('analytics');
            Route::post('/{landingPage:slug}/analytics/clear', [LandingPageTrackingController::class, 'analyticsClear'])
                ->middleware('throttle:30,1')
                ->name('analytics.clear-page');
            Route::get('/{landingPage:slug}/reporting', [LandingPageController::class, 'leads'])->name('reporting');
            Route::get('/{landingPage:slug}/bookings', function (\App\Models\LandingPage $landingPage) {
                return redirect()->route('landing-pages.reporting', $landingPage, 301);
            });
            Route::get('/{landingPage:slug}/leads', function (\App\Models\LandingPage $landingPage) {
                return redirect()->route('landing-pages.reporting', $landingPage, 301);
            });
        });
        Route::post('/settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
        Route::post('/settings/config/clear', [SettingsController::class, 'clearConfig'])->name('settings.config.clear');
        Route::post('/settings/route/clear', [SettingsController::class, 'clearRoute'])->name('settings.route.clear');
        Route::post('/settings/view/clear', [SettingsController::class, 'clearView'])->name('settings.view.clear');
        Route::post('/settings/clear-all', [SettingsController::class, 'clearAll'])->name('settings.clear-all');
        Route::post('/settings/optimize', [SettingsController::class, 'optimize'])->name('settings.optimize');
        Route::get('/settings/public-view', [SettingsController::class, 'getPublicViewSettings'])->name('settings.public-view');
        Route::get('/settings/tick-settings', [SettingsController::class, 'getTickSettingsJson'])->name('settings.tick-settings');
        Route::post('/settings/public-view', [SettingsController::class, 'savePublicViewSettings'])->name('settings.public-view.save');
        Route::get('/settings/push-notifications', [SettingsController::class, 'getPushNotificationSettings'])->name('settings.push-notifications');
        Route::post('/settings/push-notifications', [SettingsController::class, 'savePushNotificationSettings'])->name('settings.push-notifications.save');
        Route::post('/settings/upload-control', [SettingsController::class, 'saveUploadSettings'])->name('settings.upload-control.save');

        // Booth Default Settings API
        Route::get('/settings/booth-defaults', [SettingsController::class, 'getBoothDefaults'])->name('settings.booth-defaults');
        Route::post('/settings/booth-defaults', [SettingsController::class, 'saveBoothDefaults'])->name('settings.booth-defaults.save');
        Route::get('/settings/booth-master-image', [SettingsController::class, 'getMasterBoothImage'])->name('settings.booth-master-image');
        Route::post('/settings/booth-master-image', [SettingsController::class, 'uploadMasterBoothImage'])->name('settings.booth-master-image.upload');
        Route::post('/settings/booth-master-image/remove', [SettingsController::class, 'removeMasterBoothImage'])->name('settings.booth-master-image.remove');
        Route::get('/settings/booth-master-gallery', [SettingsController::class, 'getMasterBoothGallery'])->name('settings.booth-master-gallery');
        Route::post('/settings/booth-master-gallery', [SettingsController::class, 'uploadMasterBoothGalleryImage'])->name('settings.booth-master-gallery.upload');
        Route::post('/settings/booth-master-gallery/remove', [SettingsController::class, 'removeMasterBoothGalleryImage'])->name('settings.booth-master-gallery.remove');
        Route::post('/settings/booth-upload-context', [SettingsController::class, 'saveBoothUploadContext'])->name('settings.booth-upload-context.save');
        Route::post('/settings/booths-module-display', [SettingsController::class, 'saveBoothsModuleDisplay'])->name('settings.booths-module-display.save');

        // Canvas Settings API
        Route::get('/settings/canvas', [SettingsController::class, 'getCanvasSettings'])->name('settings.canvas');
        Route::post('/settings/canvas', [SettingsController::class, 'saveCanvasSettings'])->name('settings.canvas.save');

        // Booth Status Settings API
        Route::get('/settings/booth-statuses', [SettingsController::class, 'getBoothStatusSettings'])->name('settings.booth-statuses');
        Route::get('/settings/booth-statuses/colors', [SettingsController::class, 'getBoothStatusColors'])->name('settings.booth-statuses.colors');
        Route::post('/settings/booth-statuses', [SettingsController::class, 'saveBoothStatusSettings'])->name('settings.booth-statuses.save');
        Route::delete('/settings/booth-statuses/{id}', [SettingsController::class, 'deleteBoothStatusSetting'])->name('settings.booth-statuses.delete');

        // Booking status settings (bookings module labels & colors)
        Route::get('/settings/booking-statuses', [SettingsController::class, 'getBookingStatusSettings'])->name('settings.booking-statuses');
        Route::post('/settings/booking-statuses', [SettingsController::class, 'saveBookingStatusSettings'])->name('settings.booking-statuses.save');
        Route::delete('/settings/booking-statuses/{id}', [SettingsController::class, 'deleteBookingStatusSetting'])->name('settings.booking-statuses.delete');
        Route::post('/settings/booking-statuses/restore-defaults', [SettingsController::class, 'restoreBookingStatusDefaults'])->name('settings.booking-statuses.restore-defaults');

        Route::post('/settings/booth-statuses/restore-defaults', [SettingsController::class, 'restoreBoothStatusDefaults'])->name('settings.booth-statuses.restore-defaults');
        Route::post('/settings/floor-plan-colors/restore', [SettingsController::class, 'restoreFloorPlanColorDefaults'])->name('settings.floor-plan-colors.restore');
        Route::post('/settings/appearance/restore-section', [SettingsController::class, 'restoreAppearanceSection'])->name('settings.appearance.restore-section');

        // Company & Appearance Settings API
        Route::get('/settings/company', [SettingsController::class, 'getCompanySettings'])->name('settings.company');
        Route::post('/settings/company', [SettingsController::class, 'saveCompanySettings'])->name('settings.company.save');
        Route::post('/settings/company/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.company.upload-logo');
        Route::post('/settings/company/upload-favicon', [SettingsController::class, 'uploadFavicon'])->name('settings.company.upload-favicon');
        Route::get('/settings/appearance', [SettingsController::class, 'getAppearanceSettings'])->name('settings.appearance');
        Route::post('/settings/appearance', [SettingsController::class, 'saveAppearanceSettings'])->name('settings.appearance.save');

        // CDN Settings
        Route::get('/settings/cdn', [SettingsController::class, 'getCDNSettings'])->name('settings.cdn');
        Route::post('/settings/cdn', [SettingsController::class, 'saveCDNSettings'])->name('settings.cdn.save');

        // Module Display Settings
        Route::get('/settings/module-display', [SettingsController::class, 'getModuleDisplaySettings'])->name('settings.module-display');
        Route::post('/settings/module-display', [SettingsController::class, 'saveModuleDisplaySettings'])->name('settings.module-display.save');

        // Version management & Update Changelog (admin only)
        Route::get('/changelog/update', [VersionController::class, 'updateChangelogPage'])->name('changelog.update');
        Route::post('/changelog/update', [VersionController::class, 'updateCurrentVersion'])->name('changelog.update.save');
        Route::post('/changelog/append', [VersionController::class, 'appendChangelogEntry'])->name('changelog.append');
        Route::get('/versions', [VersionController::class, 'index'])->name('versions.index');
        Route::get('/versions/create', [VersionController::class, 'create'])->name('versions.create');
        Route::post('/versions', [VersionController::class, 'store'])->name('versions.store');
        Route::get('/versions/{version}', [VersionController::class, 'show'])->name('versions.show');
        Route::post('/versions/{version}/set-current', [VersionController::class, 'setCurrent'])->name('versions.set-current');

        // Git & cPanel Deployment Status
        Route::get('/git-status', [GitDeploymentController::class, 'status'])->name('git.status');
        Route::post('/git-status/refresh', [GitDeploymentController::class, 'refresh'])->name('git.status.refresh');
        Route::get('/admin/git-status', [GitDeploymentController::class, 'status'])->name('admin.git-status');
        Route::post('/admin/git-status/refresh', [GitDeploymentController::class, 'refresh'])->name('admin.git-status.refresh');

        // Image Upload Routes
        Route::prefix('images')->name('images.')->group(function () {
            Route::post('/avatar/upload', [\App\Http\Controllers\ImageController::class, 'uploadAvatar'])->name('avatar.upload');
            Route::post('/avatar/remove', [\App\Http\Controllers\ImageController::class, 'removeAvatar'])->name('avatar.remove');
            Route::post('/cover/upload', [\App\Http\Controllers\ImageController::class, 'uploadCover'])->name('cover.upload');
            Route::post('/cover/remove', [\App\Http\Controllers\ImageController::class, 'removeCover'])->name('cover.remove');
        });
    });
});

// ========================================
// Admin Event Management System Routes
// ========================================
Route::prefix('admin')->group(function () {
    // Admin Authentication Routes
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'login'])->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

    // Protected Admin Routes
    Route::middleware(['admin.auth'])->group(function () {
        // Admin Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Events Management
        Route::resource('events', EventController::class)->names([
            'index' => 'admin.events.index',
            'create' => 'admin.events.create',
            'store' => 'admin.events.store',
            'show' => 'admin.events.show',
            'edit' => 'admin.events.edit',
            'update' => 'admin.events.update',
            'destroy' => 'admin.events.destroy',
        ]);
    });
});
