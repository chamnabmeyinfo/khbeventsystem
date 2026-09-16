@extends('layouts.admin')

@section('title', 'Booking Details')
@section('page-title', 'Booking Details')
@section('breadcrumb', 'Bookings / View')


@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-looker.css') }}?v=3.6">
<style>
    /* Booking detail — single responsive layout; matches /books Looker tokens */
    .books-show-page.looker-dashboard {
        padding: 0 0 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    .books-show-page .books-show-breadcrumb {
        margin-bottom: 1rem;
    }
    .books-show-page .books-show-breadcrumb .breadcrumb {
        margin-bottom: 0;
        padding: 0.5rem 0;
        background: transparent;
        font-size: 0.9rem;
    }
    .books-show-page .books-show-breadcrumb a {
        color: var(--accent-blue);
        text-decoration: none;
        font-weight: 600;
    }
    .books-show-page .books-show-breadcrumb .active {
        color: var(--text-secondary);
        font-weight: 600;
    }
    .books-show-page .looker-header {
        margin-bottom: 1.75rem;
    }
    .books-show-page .looker-actions {
        flex-wrap: wrap;
        gap: 0.65rem;
    }
    .books-show-page .books-show-panel {
        height: 100%;
    }
    .books-show-page .books-show-detail-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .books-show-page .books-show-detail-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }
    .books-show-page .books-show-detail-row:last-child {
        border-bottom: none;
    }
    .books-show-page .books-show-detail-label {
        color: var(--text-secondary);
        font-size: 0.875rem;
        font-weight: 600;
        min-width: 0;
    }
    .books-show-page .books-show-detail-label i {
        color: var(--text-tertiary);
        width: 1.1rem;
    }
    .books-show-page .books-show-detail-value {
        text-align: right;
        font-weight: 600;
        color: var(--text-primary);
        min-width: 0;
    }
    .books-show-page .books-show-detail-value a {
        font-weight: 700;
    }
    .books-show-page .books-type-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.65rem;
        border-radius: var(--radius-pill);
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .books-show-page .books-type-regular {
        background: var(--accent-blue-light);
        color: var(--accent-blue);
    }
    .books-show-page .books-type-special {
        background: var(--accent-orange-light);
        color: var(--accent-orange);
    }
    .books-show-page .books-type-temporary {
        background: rgba(175, 82, 222, 0.18);
        color: var(--accent-purple);
    }
    .books-show-page .books-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-pill);
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }
    .books-show-page .books-amount-cell {
        color: var(--accent-green);
        font-variant-numeric: tabular-nums;
        font-weight: 800;
    }
    .books-show-page .books-show-booth-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr));
        gap: 1rem;
    }
    .books-show-page .books-show-booth-card {
        padding: 1rem 1.1rem;
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.55);
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-sm);
        transition: transform 0.14s ease, box-shadow 0.14s ease;
    }
    .books-show-page .books-show-booth-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .books-show-page .books-show-booth-card h6 {
        margin: 0;
        font-weight: 800;
    }
    .books-show-page .books-show-booth-card a {
        text-decoration: none;
    }
    .books-show-page .books-show-payment-kpis {
        margin-bottom: 1.25rem;
    }
    .books-show-page .books-show-payment-kpis .kpi-card-looker {
        margin-bottom: 0;
    }
    @media (max-width: 991.98px) {
        .books-show-page .looker-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .books-show-page .looker-actions {
            width: 100%;
        }
    }
    .hr-table-actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: nowrap;
    }
    .action-btn-icon {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 0.82rem;
    }
    .payment-structure-card-modal {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.18s ease;
        background: #fff;
        height: 100%;
    }
    .payment-structure-card-modal:hover {
        border-color: #b0c4de;
    }
    .payment-structure-card-modal.active {
        border-color: #007bff;
        background: #f0f7ff;
    }
    .payment-structure-card-modal .custom-control {
        pointer-events: none;
    }
    .calc-modal-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 15px;
    }
</style>
@endpush

@push('body-class', 'ios-dashboard-mode')


@section('content')
@php
    $typeBadgeClass = 'books-type-regular';
    if ($book->type == 2) {
        $typeBadgeClass = 'books-type-special';
    } elseif ($book->type == 3) {
        $typeBadgeClass = 'books-type-temporary';
    }
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
@endphp
<div class="looker-dashboard books-show-page">
    <nav aria-label="breadcrumb" class="books-show-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home me-1"></i>Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Bookings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Booking #{{ $book->id }}</li>
        </ol>
    </nav>

    <header class="looker-header">
        <div class="looker-header-title">
            <h1>Booking #{{ $book->id }}</h1>
            <p>
                <span class="books-type-badge {{ $typeBadgeClass }} me-2">
                    @if($book->type == 1) Regular
                    @elseif($book->type == 2) Special
                    @elseif($book->type == 3) Temporary
                    @else {{ $book->type }}
                    @endif
                </span>
                <span class="books-status-pill" id="bookingStatusBadge" style="background-color: {{ $statusColor }}; color: {{ $statusTextColor }};">
                    {{ $statusName }}
                </span>
            </p>
        </div>
        <div class="looker-actions">
            <a href="{{ route('books.index') }}" class="action-btn action-btn-secondary plastic-btn-press">
                <i class="fas fa-arrow-left"></i> Back to list
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('books.edit', $book) }}" class="action-btn action-btn-secondary plastic-btn-press">
                <i class="fas fa-edit"></i> Edit
            </a>
            @endif
            @if($book->client)
            <a href="{{ route('clients.show', $book->client) }}" class="action-btn action-btn-secondary plastic-btn-press">
                <i class="fas fa-user"></i> Client
            </a>
            @endif
            @if(!isset($payment) || !$payment)
            <a href="{{ route('finance.payments.create', ['booking_id' => $book->id]) }}" class="action-btn action-btn-primary plastic-btn-press">
                <i class="fas fa-money-bill-wave"></i> Record payment
            </a>
            @else
            <a href="{{ route('finance.payments.index', ['search' => '#'.$book->id]) }}" class="action-btn action-btn-primary plastic-btn-press">
                <i class="fas fa-receipt"></i> View payment
            </a>
            @endif
            @if(auth()->user()->isAdmin())
            <button type="button" class="action-btn action-btn-secondary plastic-btn-press" style="border-color: rgba(220, 38, 38, 0.35); color: #c53030;" onclick="deleteBooking({{ $book->id }})">
                <i class="fas fa-trash-alt"></i> Delete
            </button>
            @endif
        </div>
    </header>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="canvas-panel books-show-panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-calendar-check"></i> Booking information</h2>
                </div>
                <div class="books-show-detail-list">
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-hashtag me-2"></i>Booking ID</span>
                        <span class="books-show-detail-value text-primary">#{{ $book->id }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-calendar me-2"></i>Date &amp; time</span>
                        <span class="books-show-detail-value">
                            {{ $book->date_book->format('M d, Y') }}<br>
                            <small class="text-muted fw-normal">{{ $book->date_book->format('h:i A') }}</small>
                        </span>
                    </div>
                    <div class="books-show-detail-row align-items-center">
                        <span class="books-show-detail-label"><i class="fas fa-user me-2"></i>Booked by</span>
                        @if(auth()->user()->isAdmin() && isset($users) && $users->count() > 0)
                        <div class="books-show-detail-value" style="flex: 1; max-width: 220px;">
                            <select id="bookedByUserSelect" class="form-select form-select-sm" data-original="{{ $book->userid }}" data-original-name="{{ $book->user->username ?? 'System' }}" title="Super Admin: Reassign booking owner">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ (string)$book->userid === (string)$u->id ? 'selected' : '' }}>
                                        {{ $u->username }} ({{ $u->isAdmin() ? 'Admin' : ($u->role ? $u->role->name : 'Staff') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <span class="books-show-detail-value">{{ $book->user->username ?? 'System' }}</span>
                        @endif
                    </div>
                    @if($book->floorPlan)
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-map me-2"></i>Floor plan</span>
                        <span class="books-show-detail-value">
                            <a href="{{ route('floor-plans.show', $book->floorPlan) }}">{{ $book->floorPlan->name }}</a>
                        </span>
                    </div>
                    @if($book->floorPlan->event ?? null)
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-calendar-alt me-2"></i>Event</span>
                        <span class="books-show-detail-value">{{ $book->floorPlan->event->title ?? '—' }}</span>
                    </div>
                    @endif
                    @endif
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-cube me-2"></i>Booths</span>
                        <span class="books-show-detail-value">{{ count($booths) }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-dollar-sign me-2"></i>Total</span>
                        <span class="books-show-detail-value books-amount-cell">${{ number_format($book->total_amount ?? $booths->sum('price') ?? 0, 2) }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-check-circle me-2"></i>Paid</span>
                        <span class="books-show-detail-value" style="color: var(--accent-blue);">${{ number_format($book->paid_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-balance-scale me-2"></i>Balance</span>
                        <span class="books-show-detail-value {{ ($book->balance_amount ?? 0) > 0 ? 'text-warning' : '' }}" style="font-weight: 800;">
                            ${{ number_format($book->balance_amount ?? ($book->total_amount ?? 0) - ($book->paid_amount ?? 0), 2) }}
                        </span>
                    </div>
                    @if(auth()->user()->isAdmin())
                    <div class="books-show-detail-row align-items-center">
                        <span class="books-show-detail-label"><i class="fas fa-edit me-2"></i>Change status</span>
                        <div class="books-show-detail-value" style="flex: 1; max-width: 220px;">
                            <select id="bookingStatusSelect" class="form-select form-select-sm">
                                @if($statusSettings && $statusSettings->count() > 0)
                                    @foreach($statusSettings as $status)
                                        <option value="{{ $status->status_code }}"
                                            {{ ($book->status ?? 1) == $status->status_code ? 'selected' : '' }}
                                            data-color="{{ $status->status_color }}"
                                            data-text-color="{{ $status->text_color }}">
                                            {{ $status->status_name }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="1" {{ ($book->status ?? 1) == 1 ? 'selected' : '' }}>Pending</option>
                                    <option value="2" {{ ($book->status ?? 1) == 2 ? 'selected' : '' }}>Confirmed</option>
                                    <option value="3" {{ ($book->status ?? 1) == 3 ? 'selected' : '' }}>Reserved</option>
                                    <option value="4" {{ ($book->status ?? 1) == 4 ? 'selected' : '' }}>Paid</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="canvas-panel books-show-panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-building"></i> Client</h2>
                    @if($book->client)
                    <a href="{{ route('clients.show', $book->client) }}" class="action-btn action-btn-secondary plastic-btn-press" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">
                        <i class="fas fa-external-link-alt"></i> Profile
                    </a>
                    @endif
                </div>
                @if($book->client)
                <div class="books-show-detail-list">
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-building me-2"></i>Company</span>
                        <span class="books-show-detail-value">{{ $book->client->company ?? '—' }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-user me-2"></i>Contact</span>
                        <span class="books-show-detail-value">{{ $book->client->name }} · ID {{ $book->client->id }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-briefcase me-2"></i>Position</span>
                        <span class="books-show-detail-value">{{ $book->client->position ?? '—' }}</span>
                    </div>
                    <div class="books-show-detail-row">
                        <span class="books-show-detail-label"><i class="fas fa-phone me-2"></i>Phone</span>
                        <span class="books-show-detail-value">
                            <a href="tel:{{ $book->client->phone_number }}">{{ $book->client->phone_number ?? '—' }}</a>
                        </span>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('clients.show', $book->client) }}" class="action-btn action-btn-primary plastic-btn-press">
                            <i class="fas fa-external-link-alt"></i> Open client details
                        </a>
                    </div>
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="fas fa-user-slash fa-3x mb-3 opacity-50"></i>
                    <p class="mb-0">No client linked to this booking.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="canvas-panel books-show-panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i class="fas fa-cube"></i> Booths in this booking ({{ count($booths) }})</h2>
            <span class="badge rounded-pill px-3 py-2" style="background: var(--accent-green-light); color: var(--accent-green); font-weight: 700;">
                {{ ($booths->sum('price') ?? 0) > 0 ? '$'.number_format($booths->sum('price'), 2) : 'Free' }}
            </span>
        </div>
        @if(count($booths) > 0)
        <div class="books-show-booth-grid">
            @foreach($booths as $booth)
            <div class="books-show-booth-card">
                @php $boothStatusColors = $booth->getStatusColors(); @endphp
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6>
                            <a href="{{ route('booths.show', $booth) }}" class="text-primary">
                                <i class="fas fa-cube me-1"></i>{{ $booth->booth_number }}
                            </a>
                        </h6>
                        @if($booth->category)
                        <small class="text-muted"><i class="fas fa-folder me-1"></i>{{ $booth->category->name }}</small>
                        @endif
                    </div>
                    <span class="badge" style="background-color: {{ $boothStatusColors['background'] }}; border: 1px solid {{ $boothStatusColors['border'] }}; color: {{ $boothStatusColors['text'] }};">{{ $booth->getStatusLabel() }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small">Price</span>
                    <strong class="books-amount-cell">${{ number_format($booth->price, 2) }}</strong>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="fas fa-cube fa-3x mb-3 opacity-50"></i>
            <p class="mb-0">No booths in this booking.</p>
        </div>
        @endif
    </div>

    <div class="canvas-panel books-show-panel">
        <div class="panel-header">
            <h2 class="panel-title"><i class="fas fa-money-bill-wave"></i> Payments</h2>
            <div class="d-flex align-items-center">
                <button type="button" class="action-btn action-btn-primary plastic-btn-press mr-2" data-toggle="modal" data-target="#recordPaymentModal">
                    <i class="fas fa-plus"></i> Record payment
                </button>
                <a href="{{ route('finance.payments.create', ['booking_id' => $book->id, 'redirect_to' => 'booking']) }}" class="action-btn action-btn-secondary" title="Full payment form">
                    <i class="fas fa-external-link-alt"></i> Full form
                </a>
            </div>
        </div>

        <div class="kpi-wrapper books-show-payment-kpis">
            <div class="kpi-card-looker">
                <div class="kpi-top">
                    <div class="kpi-title">Total</div>
                    <div class="kpi-icon-wrapper primary-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
                <div class="kpi-value-looker books-amount-cell">${{ number_format($book->total_amount ?? 0, 2) }}</div>
                <div class="kpi-bottom trend-neutral">Booking total</div>
            </div>
            <div class="kpi-card-looker success">
                <div class="kpi-top">
                    <div class="kpi-title">Paid</div>
                    <div class="kpi-icon-wrapper success-icon"><i class="fas fa-check"></i></div>
                </div>
                <div class="kpi-value-looker" style="color: var(--accent-blue);">${{ number_format($book->paid_amount ?? 0, 2) }}</div>
                <div class="kpi-bottom trend-positive">Received</div>
            </div>
            <div class="kpi-card-looker {{ ($book->balance_amount ?? 0) > 0 ? 'warning' : 'success' }}">
                <div class="kpi-top">
                    <div class="kpi-title">Balance</div>
                    <div class="kpi-icon-wrapper {{ ($book->balance_amount ?? 0) > 0 ? 'warning-icon' : 'success-icon' }}"><i class="fas fa-scale-balanced"></i></div>
                </div>
                <div class="kpi-value-looker {{ ($book->balance_amount ?? 0) > 0 ? 'trend-warning' : '' }}" style="color: {{ ($book->balance_amount ?? 0) > 0 ? 'var(--accent-orange)' : 'var(--accent-green)' }};">
                    ${{ number_format($book->balance_amount ?? 0, 2) }}
                </div>
                <div class="kpi-bottom {{ ($book->balance_amount ?? 0) > 0 ? 'trend-warning' : 'trend-positive' }}">
                    {{ ($book->balance_amount ?? 0) > 0 ? 'Outstanding' : 'Settled' }}
                </div>
            </div>
        </div>

        @if($payments && $payments->count() > 0)
        <h3 class="h6 fw-bold text-secondary mb-3"><i class="fas fa-history me-2"></i>Payment history</h3>
        <div class="looker-table-wrapper">
            <table class="looker-table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Recorded by</th>
                        <th>Notes</th>
                        <th style="min-width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr id="payment-row-{{ $payment->id }}">
                        <td>{{ $payment->paid_at->format('M d, Y h:i A') }}</td>
                        <td><strong class="books-amount-cell">${{ number_format($payment->amount, 2) }}</strong></td>
                        <td>
                            @php
                                $isSplit = method_exists($payment, 'isSplit') ? $payment->isSplit() : ($payment->payment_method === 'split');
                                $isProdExchange = method_exists($payment, 'isProductExchange') ? $payment->isProductExchange() : ($payment->payment_method === 'product_exchange');
                            @endphp
                            @if($isSplit)
                                <span class="status-badge status-badge-purple" title="Split payment">
                                    <i class="fas fa-layer-group mr-1"></i> Split Payment
                                </span>
                                <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                    <span class="text-success">${{ number_format($payment->cash_amount ?? 0, 2) }}</span> + 
                                    <span class="text-info">${{ number_format($payment->product_amount ?? 0, 2) }} prod</span>
                                </div>
                            @elseif($isProdExchange)
                                <span class="status-badge status-badge-indigo" title="Product exchange barter">
                                    <i class="fas fa-boxes mr-1"></i> Product Exchange
                                </span>
                            @else
                                <span class="status-badge status-badge-blue">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status === 'completed')
                                <span class="status-badge status-badge-green">{{ ucfirst($payment->status) }}</span>
                            @elseif($payment->status === 'pending')
                                <span class="status-badge" style="background: var(--accent-orange-light); color: var(--accent-orange); font-weight: 700;">{{ ucfirst($payment->status) }}</span>
                            @else
                                <span class="status-badge" style="background: rgba(220, 38, 38, 0.12); color: #dc2626; font-weight: 700;">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $payment->user->username ?? 'System' }}</td>
                        <td>{{ Str::limit($payment->notes ?? '—', 80) }}</td>
                        <td>
                            <div class="hr-table-actions">
                                <button type="button" class="action-btn action-btn-secondary action-btn-icon"
                                        onclick="viewPaymentDetails({{ json_encode($payment) }}, '{{ addslashes($payment->user->username ?? 'System') }}')"
                                        title="View payment receipt details">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                                <a href="{{ route('finance.payments.invoice', $payment->id) }}"
                                   class="action-btn action-btn-secondary action-btn-icon"
                                   target="_blank" rel="noopener noreferrer"
                                   title="Print invoice">
                                    <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                </a>
                                <button type="button" class="action-btn action-btn-secondary action-btn-icon"
                                        onclick="openEditPaymentModal({{ json_encode($payment) }})"
                                        title="Edit payment">
                                    <i class="fas fa-edit" aria-hidden="true"></i>
                                </button>
                                @if($payment->status === \App\Models\Payment::STATUS_COMPLETED)
                                <button type="button" class="action-btn action-btn-secondary action-btn-icon"
                                        onclick="refundBookingPayment({{ $payment->id }}, '{{ number_format($payment->amount, 2) }}')"
                                        title="Refund payment">
                                    <i class="fas fa-undo" aria-hidden="true"></i>
                                </button>
                                @endif
                                @if(in_array($payment->status, [\App\Models\Payment::STATUS_COMPLETED, \App\Models\Payment::STATUS_PENDING]))
                                <button type="button" class="action-btn action-btn-secondary action-btn-icon"
                                        onclick="voidBookingPayment({{ $payment->id }})"
                                        title="Void payment">
                                    <i class="fas fa-ban" aria-hidden="true"></i>
                                </button>
                                @endif
                                <button type="button" class="action-btn action-btn-danger-soft action-btn-icon"
                                        onclick="deleteBookingPayment({{ $payment->id }}, '{{ number_format($payment->amount, 2) }}')"
                                        title="Delete payment">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="fas fa-money-bill-wave fa-3x mb-3 opacity-50"></i>
            <p class="mb-3">No payments recorded yet.</p>
            <button type="button" class="action-btn action-btn-primary plastic-btn-press mr-2" data-toggle="modal" data-target="#recordPaymentModal">
                <i class="fas fa-plus"></i> Record first payment
            </button>
            <a href="{{ route('finance.payments.create', ['booking_id' => $book->id, 'redirect_to' => 'booking']) }}" class="action-btn action-btn-secondary">
                <i class="fas fa-external-link-alt"></i> Full form
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Modal 1: Quick Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" role="dialog" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <form action="{{ route('finance.payments.store') }}" method="POST" id="bookingQuickPaymentForm">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $book->id }}">
                <input type="hidden" name="client_id" value="{{ $book->client_id }}">
                <input type="hidden" name="redirect_to" value="booking">

                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title font-weight-bold" id="recordPaymentModalLabel">
                        <i class="fas fa-money-bill-wave text-primary mr-2"></i>Record Payment for Booking #{{ $book->id }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <!-- Structure selector -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold small text-uppercase text-muted">Payment Structure <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="payment-structure-card-modal active" id="modalRecCardStandard" onclick="setQuickRecordStructure('standard')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="modal_rec_standard" name="payment_structure" value="standard" class="custom-control-input" checked>
                                        <label class="custom-control-label font-weight-bold" for="modal_rec_standard">
                                            <i class="fas fa-money-bill-wave text-success mr-1"></i>Standard Money
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">100% Cash / Bank / Online</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="payment-structure-card-modal" id="modalRecCardSplit" onclick="setQuickRecordStructure('split')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="modal_rec_split" name="payment_structure" value="split" class="custom-control-input">
                                        <label class="custom-control-label font-weight-bold" for="modal_rec_split">
                                            <i class="fas fa-layer-group text-primary mr-1"></i>Split Payment
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Money + Product Barter</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="payment-structure-card-modal" id="modalRecCardProduct" onclick="setQuickRecordStructure('product_exchange')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="modal_rec_product" name="payment_structure" value="product_exchange" class="custom-control-input">
                                        <label class="custom-control-label font-weight-bold" for="modal_rec_product">
                                            <i class="fas fa-boxes text-info mr-1"></i>Product Exchange
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">100% Goods Deduction</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amounts input -->
                    <div class="row">
                        <div class="col-md-6" id="modalRecWrapperStandard">
                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="modal_rec_amount" class="font-weight-bold mb-0">Total Amount ($) <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="quickFillRecBalance()">
                                        Fill Balance (${{ number_format($book->balance_amount > 0 ? $book->balance_amount : ($book->total_amount ?? 0), 2) }})
                                    </button>
                                </div>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text font-weight-bold">$</span></div>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="modal_rec_amount" 
                                           class="form-control form-control-lg font-weight-bold" 
                                           value="{{ $book->balance_amount > 0 ? $book->balance_amount : ($book->total_amount ?? '') }}" 
                                           oninput="updateQuickRecCalc()" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="modalRecWrapperCash" style="display: none;">
                            <div class="form-group mb-3">
                                <label for="modal_rec_cash_amount" class="font-weight-bold text-success">
                                    <i class="fas fa-coins mr-1"></i>Cash / Transfer Portion ($) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text text-success font-weight-bold">$</span></div>
                                    <input type="number" step="0.01" min="0" name="cash_amount" id="modal_rec_cash_amount" 
                                           class="form-control form-control-lg font-weight-bold" placeholder="0.00" oninput="updateQuickRecCalc()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="modalRecWrapperProduct" style="display: none;">
                            <div class="form-group mb-3">
                                <label for="modal_rec_product_amount" class="font-weight-bold text-info">
                                    <i class="fas fa-box-open mr-1"></i>Product Exchange Deduction ($) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text text-info font-weight-bold">$</span></div>
                                    <input type="number" step="0.01" min="0" name="product_amount" id="modal_rec_product_amount" 
                                           class="form-control form-control-lg font-weight-bold" placeholder="0.00" oninput="updateQuickRecCalc()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="modalRecWrapperMethod">
                            <div class="form-group mb-3">
                                <label for="modal_rec_method" class="font-weight-bold" id="modalRecLblMethod">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="modal_rec_method" class="form-control form-control-lg" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="online">Online Payment</option>
                                    <option value="check">Check</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Product Terms -->
                    <div class="form-group mb-3" id="modalRecWrapperProductDetails" style="display: none;">
                        <label for="modal_rec_product_details" class="font-weight-bold text-info">
                            <i class="fas fa-clipboard-list mr-1"></i>Product Barter Items & Description
                        </label>
                        <textarea name="product_details" id="modal_rec_product_details" rows="2" 
                                  class="form-control" placeholder="Describe goods received for barter deduction..."></textarea>
                    </div>

                    <!-- Calculation Box -->
                    <div class="calc-modal-box mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted font-weight-bold">Total Credited Value:</span>
                            <span class="font-weight-bold text-primary" id="modalRecTotalCredited" style="font-size: 1.25rem;">
                                ${{ number_format($book->balance_amount > 0 ? $book->balance_amount : ($book->total_amount ?? 0), 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Date & Notes -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_rec_paid_at" class="font-weight-bold">Payment Date</label>
                                <input type="datetime-local" name="paid_at" id="modal_rec_paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="modal_rec_transaction_id">Transaction / Reference ID</label>
                                <input type="text" name="transaction_id" id="modal_rec_transaction_id" class="form-control" placeholder="Optional reference #">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label for="modal_rec_notes">Notes</label>
                                <textarea name="notes" id="modal_rec_notes" class="form-control" rows="1" placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <form action="" method="POST" id="editPaymentModalForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="booking">

                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title font-weight-bold" id="editPaymentModalLabel">
                        <i class="fas fa-edit text-primary mr-2"></i>Edit Payment #<span id="editModalPaymentIdText"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <!-- Structure selector -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold small text-uppercase text-muted">Payment Structure <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="payment-structure-card-modal active" id="modalEditCardStandard" onclick="setEditStructure('standard')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="modal_edit_standard" name="payment_structure" value="standard" class="custom-control-input" checked>
                                        <label class="custom-control-label font-weight-bold" for="modal_edit_standard">
                                            <i class="fas fa-money-bill-wave text-success mr-1"></i>Standard Money
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">100% Cash / Bank / Online</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="payment-structure-card-modal" id="modalEditCardSplit" onclick="setEditStructure('split')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="modal_edit_split" name="payment_structure" value="split" class="custom-control-input">
                                        <label class="custom-control-label font-weight-bold" for="modal_edit_split">
                                            <i class="fas fa-layer-group text-primary mr-1"></i>Split Payment
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Money + Product Barter</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="payment-structure-card-modal" id="modalEditCardProduct" onclick="setEditStructure('product_exchange')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="modal_edit_product" name="payment_structure" value="product_exchange" class="custom-control-input">
                                        <label class="custom-control-label font-weight-bold" for="modal_edit_product">
                                            <i class="fas fa-boxes text-info mr-1"></i>Product Exchange
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">100% Goods Deduction</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amounts input -->
                    <div class="row">
                        <div class="col-md-6" id="modalEditWrapperStandard">
                            <div class="form-group mb-3">
                                <label for="modal_edit_amount" class="font-weight-bold">Total Payment Amount ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text font-weight-bold">$</span></div>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="modal_edit_amount" 
                                           class="form-control form-control-lg font-weight-bold" oninput="updateEditCalc()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="modalEditWrapperCash" style="display: none;">
                            <div class="form-group mb-3">
                                <label for="modal_edit_cash_amount" class="font-weight-bold text-success">
                                    <i class="fas fa-coins mr-1"></i>Cash / Transfer Portion ($) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text text-success font-weight-bold">$</span></div>
                                    <input type="number" step="0.01" min="0" name="cash_amount" id="modal_edit_cash_amount" 
                                           class="form-control form-control-lg font-weight-bold" oninput="updateEditCalc()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="modalEditWrapperProduct" style="display: none;">
                            <div class="form-group mb-3">
                                <label for="modal_edit_product_amount" class="font-weight-bold text-info">
                                    <i class="fas fa-box-open mr-1"></i>Product Exchange Deduction ($) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text text-info font-weight-bold">$</span></div>
                                    <input type="number" step="0.01" min="0" name="product_amount" id="modal_edit_product_amount" 
                                           class="form-control form-control-lg font-weight-bold" oninput="updateEditCalc()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="modalEditWrapperMethod">
                            <div class="form-group mb-3">
                                <label for="modal_edit_method" class="font-weight-bold" id="modalEditLblMethod">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="modal_edit_method" class="form-control form-control-lg" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="online">Online Payment</option>
                                    <option value="check">Check</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Product Terms -->
                    <div class="form-group mb-3" id="modalEditWrapperProductDetails" style="display: none;">
                        <label for="modal_edit_product_details" class="font-weight-bold text-info">
                            <i class="fas fa-clipboard-list mr-1"></i>Product Barter Items & Description
                        </label>
                        <textarea name="product_details" id="modal_edit_product_details" rows="2" 
                                  class="form-control" placeholder="Describe goods received for barter deduction..."></textarea>
                    </div>

                    <!-- Calculation Box -->
                    <div class="calc-modal-box mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted font-weight-bold">Total Credited Value:</span>
                            <span class="font-weight-bold text-primary" id="modalEditTotalCredited" style="font-size: 1.25rem;">$0.00</span>
                        </div>
                    </div>

                    <!-- Status, Date, Notes -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="modal_edit_status" class="font-weight-bold">Status <span class="text-danger">*</span></label>
                                <select name="status" id="modal_edit_status" class="form-control" required>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="modal_edit_paid_at" class="font-weight-bold">Date & Time</label>
                                <input type="datetime-local" name="paid_at" id="modal_edit_paid_at" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="modal_edit_transaction_id">Transaction ID</label>
                                <input type="text" name="transaction_id" id="modal_edit_transaction_id" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label for="modal_edit_notes">Notes</label>
                                <textarea name="notes" id="modal_edit_notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top d-flex justify-content-between">
                    <a href="" id="modalEditFullPageLink" class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="fas fa-external-link-alt mr-1"></i>Full Edit Page
                    </a>
                    <div>
                        <button type="button" class="btn btn-secondary mr-1" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: View Payment Details Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" role="dialog" aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title font-weight-bold" id="viewPaymentModalLabel">
                    <i class="fas fa-receipt text-primary mr-2"></i>Payment Receipt #<span id="viewModalId"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="p-4 bg-light border-bottom text-center">
                    <span class="text-muted text-uppercase small font-weight-bold d-block">Total Value Credited</span>
                    <h2 class="text-primary font-weight-bold my-1" id="viewModalAmount">$0.00</h2>
                    <div id="viewModalStatusBadge" class="mt-2"></div>
                </div>
                <div class="p-4">
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Payment Date:</div>
                        <div class="col-6 text-right font-weight-bold text-dark" id="viewModalDate">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Payment Method:</div>
                        <div class="col-6 text-right font-weight-bold" id="viewModalMethod">-</div>
                    </div>
                    <div id="viewModalSplitBreakdown" style="display: none;">
                        <div class="row mb-2 pl-3">
                            <div class="col-6 text-success"><i class="fas fa-coins mr-1"></i>Money Portion:</div>
                            <div class="col-6 text-right font-weight-bold text-success" id="viewModalCashPortion">$0.00</div>
                        </div>
                        <div class="row mb-2 pl-3">
                            <div class="col-6 text-info"><i class="fas fa-boxes mr-1"></i>Product Deduction:</div>
                            <div class="col-6 text-right font-weight-bold text-info" id="viewModalProductPortion">$0.00</div>
                        </div>
                    </div>
                    <div id="viewModalProductTermsRow" class="mb-2" style="display: none;">
                        <div class="text-muted small mb-1"><i class="fas fa-info-circle mr-1"></i>Exchanged Items / Terms:</div>
                        <div class="p-2 rounded bg-light border small text-dark" id="viewModalProductTerms">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Recorded By:</div>
                        <div class="col-6 text-right text-dark" id="viewModalUser">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Transaction Ref:</div>
                        <div class="col-6 text-right text-dark" id="viewModalTxn">-</div>
                    </div>
                    <div class="mt-3 pt-2 border-top">
                        <span class="text-muted small d-block">Notes:</span>
                        <p class="text-dark small mb-0 mt-1" id="viewModalNotes">—</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top d-flex justify-content-between">
                <a href="" id="viewModalInvoiceBtn" target="_blank" class="btn btn-outline-primary btn-sm font-weight-bold">
                    <i class="fas fa-file-invoice mr-1"></i>Print Invoice
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if(auth()->user()->isAdmin())
document.getElementById('bookingStatusSelect')?.addEventListener('change', function() {
    const status = this.value;
    const selectedOption = this.options[this.selectedIndex];
    const statusColor = selectedOption.getAttribute('data-color');
    const statusTextColor = selectedOption.getAttribute('data-text-color');

    Swal.fire({
        title: 'Update booking status?',
        text: 'Change the status for this booking?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: statusColor || '#007AFF',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, update',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ route('books.update-status', $book->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const statusBadge = document.getElementById('bookingStatusBadge');
                    if (statusBadge) {
                        statusBadge.textContent = data.status_label;
                        statusBadge.style.backgroundColor = statusColor;
                        statusBadge.style.color = statusTextColor;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Status updated',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    this.value = '{{ $book->status ?? 1 }}';
                    Swal.fire('Error', data.message || 'Failed to update status.', 'error');
                }
            })
            .catch(error => {
                this.value = '{{ $book->status ?? 1 }}';
                Swal.fire('Error', 'An error occurred while updating status.', 'error');
                console.error('Error:', error);
            });
        } else {
            this.value = '{{ $book->status ?? 1 }}';
        }
    });
});

document.getElementById('bookedByUserSelect')?.addEventListener('change', function() {
    const newUserId = this.value;
    const selectedOption = this.options[this.selectedIndex];
    const newUsername = selectedOption.textContent.trim();
    const originalUserId = this.getAttribute('data-original');

    Swal.fire({
        title: 'Reassign booking owner?',
        text: `Reassign this booking and all its linked booths to ${newUsername}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007AFF',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reassign',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ route('books.reassign-user', $book->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ userid: newUserId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.setAttribute('data-original', newUserId);
                    Swal.fire({
                        icon: 'success',
                        title: 'Owner Reassigned',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    this.value = originalUserId;
                    Swal.fire('Error', data.message || 'Failed to reassign booking.', 'error');
                }
            })
            .catch(error => {
                this.value = originalUserId;
                Swal.fire('Error', 'An error occurred while reassigning booking.', 'error');
                console.error('Error:', error);
            });
        } else {
            this.value = originalUserId;
        }
    });
});
@endif

function deleteBooking(id) {
    Swal.fire({
        title: 'Delete booking?',
        text: 'This will release all booths in this booking. This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            fetch(`/books/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    Swal.fire('Deleted', data.message || 'Booking has been deleted.', 'success')
                        .then(() => {
                            window.location.href = '{{ route("books.index") }}';
                        });
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete booking.', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                Swal.fire('Error', 'An error occurred while deleting the booking.', 'error');
                console.error('Error:', error);
            });
        }
    });
}

// ==========================================
// PAYMENT HISTORY CRUD FUNCTIONS
// ==========================================

function setQuickRecordStructure(structure) {
    document.getElementById('modalRecCardStandard')?.classList.toggle('active', structure === 'standard');
    document.getElementById('modalRecCardSplit')?.classList.toggle('active', structure === 'split');
    document.getElementById('modalRecCardProduct')?.classList.toggle('active', structure === 'product_exchange');

    const wrapperStandard = document.getElementById('modalRecWrapperStandard');
    const wrapperCash = document.getElementById('modalRecWrapperCash');
    const wrapperProduct = document.getElementById('modalRecWrapperProduct');
    const wrapperMethod = document.getElementById('modalRecWrapperMethod');
    const wrapperDetails = document.getElementById('modalRecWrapperProductDetails');
    const lblMethod = document.getElementById('modalRecLblMethod');

    if (structure === 'standard') {
        if (wrapperStandard) wrapperStandard.style.display = '';
        if (wrapperCash) wrapperCash.style.display = 'none';
        if (wrapperProduct) wrapperProduct.style.display = 'none';
        if (wrapperMethod) wrapperMethod.style.display = '';
        if (wrapperDetails) wrapperDetails.style.display = 'none';
        if (lblMethod) lblMethod.textContent = 'Payment Method';
    } else if (structure === 'split') {
        if (wrapperStandard) wrapperStandard.style.display = 'none';
        if (wrapperCash) wrapperCash.style.display = '';
        if (wrapperProduct) wrapperProduct.style.display = '';
        if (wrapperMethod) wrapperMethod.style.display = '';
        if (wrapperDetails) wrapperDetails.style.display = '';
        if (lblMethod) lblMethod.textContent = 'Cash/Transfer Method';
    } else if (structure === 'product_exchange') {
        if (wrapperStandard) wrapperStandard.style.display = 'none';
        if (wrapperCash) wrapperCash.style.display = 'none';
        if (wrapperProduct) wrapperProduct.style.display = '';
        if (wrapperMethod) wrapperMethod.style.display = 'none';
        if (wrapperDetails) wrapperDetails.style.display = '';
    }

    updateQuickRecCalc();
}

function updateQuickRecCalc() {
    const isStandard = document.getElementById('modal_rec_standard')?.checked;
    const isSplit = document.getElementById('modal_rec_split')?.checked;
    const isProduct = document.getElementById('modal_rec_product')?.checked;

    let total = 0;
    if (isStandard) {
        total = parseFloat(document.getElementById('modal_rec_amount')?.value) || 0;
    } else if (isSplit) {
        const cash = parseFloat(document.getElementById('modal_rec_cash_amount')?.value) || 0;
        const product = parseFloat(document.getElementById('modal_rec_product_amount')?.value) || 0;
        total = cash + product;
    } else if (isProduct) {
        total = parseFloat(document.getElementById('modal_rec_product_amount')?.value) || 0;
    }

    const badge = document.getElementById('modalRecTotalCredited');
    if (badge) badge.textContent = '$' + total.toFixed(2);
}

function quickFillRecBalance() {
    const balance = {{ (float) ($book->balance_amount > 0 ? $book->balance_amount : ($book->total_amount ?? 0)) }};
    const isStandard = document.getElementById('modal_rec_standard')?.checked;
    const isSplit = document.getElementById('modal_rec_split')?.checked;
    const isProduct = document.getElementById('modal_rec_product')?.checked;

    if (isStandard) {
        const input = document.getElementById('modal_rec_amount');
        if (input) input.value = balance.toFixed(2);
    } else if (isSplit) {
        const cashInput = document.getElementById('modal_rec_cash_amount');
        if (cashInput && (!cashInput.value || parseFloat(cashInput.value) === 0)) {
            cashInput.value = balance.toFixed(2);
        }
    } else if (isProduct) {
        const prodInput = document.getElementById('modal_rec_product_amount');
        if (prodInput) prodInput.value = balance.toFixed(2);
    }
    updateQuickRecCalc();
}

function setEditStructure(structure) {
    document.getElementById('modalEditCardStandard')?.classList.toggle('active', structure === 'standard');
    document.getElementById('modalEditCardSplit')?.classList.toggle('active', structure === 'split');
    document.getElementById('modalEditCardProduct')?.classList.toggle('active', structure === 'product_exchange');

    const wrapperStandard = document.getElementById('modalEditWrapperStandard');
    const wrapperCash = document.getElementById('modalEditWrapperCash');
    const wrapperProduct = document.getElementById('modalEditWrapperProduct');
    const wrapperMethod = document.getElementById('modalEditWrapperMethod');
    const wrapperDetails = document.getElementById('modalEditWrapperProductDetails');
    const lblMethod = document.getElementById('modalEditLblMethod');

    if (structure === 'standard') {
        if (wrapperStandard) wrapperStandard.style.display = '';
        if (wrapperCash) wrapperCash.style.display = 'none';
        if (wrapperProduct) wrapperProduct.style.display = 'none';
        if (wrapperMethod) wrapperMethod.style.display = '';
        if (wrapperDetails) wrapperDetails.style.display = 'none';
        if (lblMethod) lblMethod.textContent = 'Payment Method';
    } else if (structure === 'split') {
        if (wrapperStandard) wrapperStandard.style.display = 'none';
        if (wrapperCash) wrapperCash.style.display = '';
        if (wrapperProduct) wrapperProduct.style.display = '';
        if (wrapperMethod) wrapperMethod.style.display = '';
        if (wrapperDetails) wrapperDetails.style.display = '';
        if (lblMethod) lblMethod.textContent = 'Cash/Transfer Method';
    } else if (structure === 'product_exchange') {
        if (wrapperStandard) wrapperStandard.style.display = 'none';
        if (wrapperCash) wrapperCash.style.display = 'none';
        if (wrapperProduct) wrapperProduct.style.display = '';
        if (wrapperMethod) wrapperMethod.style.display = 'none';
        if (wrapperDetails) wrapperDetails.style.display = '';
    }

    updateEditCalc();
}

function updateEditCalc() {
    const isStandard = document.getElementById('modal_edit_standard')?.checked;
    const isSplit = document.getElementById('modal_edit_split')?.checked;
    const isProduct = document.getElementById('modal_edit_product')?.checked;

    let total = 0;
    if (isStandard) {
        total = parseFloat(document.getElementById('modal_edit_amount')?.value) || 0;
    } else if (isSplit) {
        const cash = parseFloat(document.getElementById('modal_edit_cash_amount')?.value) || 0;
        const product = parseFloat(document.getElementById('modal_edit_product_amount')?.value) || 0;
        total = cash + product;
    } else if (isProduct) {
        total = parseFloat(document.getElementById('modal_edit_product_amount')?.value) || 0;
    }

    const badge = document.getElementById('modalEditTotalCredited');
    if (badge) badge.textContent = '$' + total.toFixed(2);
}

function openEditPaymentModal(payment) {
    if (!payment) return;

    document.getElementById('editModalPaymentIdText').textContent = payment.id;
    document.getElementById('editPaymentModalForm').action = `/finance/payments/${payment.id}`;
    document.getElementById('modalEditFullPageLink').href = `/finance/payments/${payment.id}/edit`;

    const isSplit = (payment.payment_method === 'split' || (payment.cash_amount > 0 && payment.product_amount > 0));
    const isProduct = (payment.payment_method === 'product_exchange');
    const structure = isSplit ? 'split' : (isProduct ? 'product_exchange' : 'standard');

    document.getElementById('modal_edit_standard').checked = (structure === 'standard');
    document.getElementById('modal_edit_split').checked = (structure === 'split');
    document.getElementById('modal_edit_product').checked = (structure === 'product_exchange');

    document.getElementById('modal_edit_amount').value = parseFloat(payment.amount || 0).toFixed(2);
    document.getElementById('modal_edit_cash_amount').value = parseFloat(payment.cash_amount || payment.amount || 0).toFixed(2);
    document.getElementById('modal_edit_product_amount').value = parseFloat(payment.product_amount || (isProduct ? payment.amount : 0)).toFixed(2);
    document.getElementById('modal_edit_product_details').value = payment.product_details || '';

    const methodSelect = document.getElementById('modal_edit_method');
    if (methodSelect) {
        methodSelect.value = payment.cash_payment_method || (payment.payment_method !== 'split' && payment.payment_method !== 'product_exchange' ? payment.payment_method : 'cash');
    }

    const statusSelect = document.getElementById('modal_edit_status');
    if (statusSelect) {
        statusSelect.value = payment.status || 'completed';
    }

    if (payment.paid_at) {
        const d = new Date(payment.paid_at);
        const pad = (n) => String(n).padStart(2, '0');
        const formattedDate = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        document.getElementById('modal_edit_paid_at').value = formattedDate;
    }

    document.getElementById('modal_edit_transaction_id').value = payment.transaction_id || '';
    document.getElementById('modal_edit_notes').value = payment.notes || '';

    setEditStructure(structure);
    $('#editPaymentModal').modal('show');
}

function viewPaymentDetails(payment, username) {
    if (!payment) return;

    document.getElementById('viewModalId').textContent = payment.id;
    document.getElementById('viewModalAmount').textContent = '$' + parseFloat(payment.amount || 0).toFixed(2);

    const statusColors = {
        'completed': 'badge-success',
        'pending': 'badge-warning',
        'failed': 'badge-danger',
        'refunded': 'badge-purple'
    };
    const badgeClass = statusColors[payment.status] || 'badge-secondary';
    document.getElementById('viewModalStatusBadge').innerHTML = `<span class="badge ${badgeClass} px-3 py-1 font-weight-bold text-uppercase" style="font-size: 0.85rem;">${payment.status || 'completed'}</span>`;

    let dateStr = '—';
    if (payment.paid_at) {
        const d = new Date(payment.paid_at);
        dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    document.getElementById('viewModalDate').textContent = dateStr;

    const isSplit = (payment.payment_method === 'split' || (payment.cash_amount > 0 && payment.product_amount > 0));
    const isProduct = (payment.payment_method === 'product_exchange');

    let methodLabel = (payment.payment_method || '').replace('_', ' ').toUpperCase();
    if (isSplit) methodLabel = 'SPLIT (MONEY + PRODUCT)';
    if (isProduct) methodLabel = 'PRODUCT EXCHANGE BARTER';
    document.getElementById('viewModalMethod').textContent = methodLabel;

    const splitBox = document.getElementById('viewModalSplitBreakdown');
    if (isSplit) {
        splitBox.style.display = '';
        document.getElementById('viewModalCashPortion').textContent = '$' + parseFloat(payment.cash_amount || 0).toFixed(2);
        document.getElementById('viewModalProductPortion').textContent = '$' + parseFloat(payment.product_amount || 0).toFixed(2);
    } else {
        splitBox.style.display = 'none';
    }

    const termsRow = document.getElementById('viewModalProductTermsRow');
    if ((isSplit || isProduct) && payment.product_details) {
        termsRow.style.display = '';
        document.getElementById('viewModalProductTerms').textContent = payment.product_details;
    } else {
        termsRow.style.display = 'none';
    }

    document.getElementById('viewModalUser').textContent = username || 'System';
    document.getElementById('viewModalTxn').textContent = payment.transaction_id || '—';
    document.getElementById('viewModalNotes').textContent = payment.notes || 'No notes provided.';
    document.getElementById('viewModalInvoiceBtn').href = `/finance/payments/${payment.id}/invoice`;

    $('#viewPaymentModal').modal('show');
}

function deleteBookingPayment(paymentId, amountFormatted) {
    Swal.fire({
        title: 'Delete Payment #' + paymentId + '?',
        html: `Are you sure you want to permanently delete payment <strong>#${paymentId}</strong> ($${amountFormatted}) from this booking?<br><br>• Booking paid amount and remaining balance will be recalculated immediately.<br>• Booth payment status will update automatically.<br><br><strong class="text-danger">This action cannot be undone!</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete payment!',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/finance/payments/${paymentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Delete failed'); });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Deleted',
                text: result.value.message || 'Payment has been deleted.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        }
    });
}

function refundBookingPayment(paymentId, amountFormatted) {
    Swal.fire({
        title: 'Refund Payment #' + paymentId + '?',
        html: `Are you sure you want to refund payment <strong>#${paymentId}</strong> ($${amountFormatted})?<br><br>This will create a refund credit and adjust booking and booth balances.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#007AFF',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, refund',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Optional: Refund reason or notes...',
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            return fetch(`/finance/payments/${paymentId}/refund`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes || '', redirect_to: 'booking' })
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed || result.value) {
            location.reload();
        }
    });
}

function voidBookingPayment(paymentId) {
    Swal.fire({
        title: 'Void Payment #' + paymentId + '?',
        html: `Are you sure you want to void payment <strong>#${paymentId}</strong>?<br><br>This marks the payment as failed/voided and recalculates balances.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, void it',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Optional: Void reason...',
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            return fetch(`/finance/payments/${paymentId}/void`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes || '', redirect_to: 'booking' })
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed || result.value) {
            location.reload();
        }
    });
}
</script>
@endpush
