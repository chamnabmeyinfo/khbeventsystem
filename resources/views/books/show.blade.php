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
            <a href="{{ route('finance.payments.create', ['booking_id' => $book->id]) }}" class="action-btn action-btn-primary plastic-btn-press">
                <i class="fas fa-plus"></i> Record payment
            </a>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('M d, Y h:i A') }}</td>
                        <td><strong class="books-amount-cell">${{ number_format($payment->amount, 2) }}</strong></td>
                        <td><span class="status-badge status-badge-blue">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span></td>
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="fas fa-money-bill-wave fa-3x mb-3 opacity-50"></i>
            <p class="mb-3">No payments recorded yet.</p>
            <a href="{{ route('finance.payments.create', ['booking_id' => $book->id]) }}" class="action-btn action-btn-primary plastic-btn-press">
                <i class="fas fa-plus"></i> Record first payment
            </a>
        </div>
        @endif
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
</script>
@endpush
