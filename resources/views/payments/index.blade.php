@extends('layouts.adminlte')

@section('title', 'Payments')
@section('page-title', 'Payment Management')
@section('breadcrumb', 'Finance / Payments')

@push('body-class', 'ios-dashboard-mode')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-looker.css') }}?v=3.7">
<link rel="stylesheet" href="{{ asset('css/hr-looker.css') }}?v=1">
<style>
    /* Finance / payments index — single responsive layer; mirrors HR list padding (dashboard-looker breakpoints). */
    .payments-index-page.looker-dashboard {
        padding-left: 0;
        padding-right: 0;
        max-width: 100%;
    }
    @media (min-width: 576px) {
        .payments-index-page.looker-dashboard {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
    }
    .payments-amount-strong {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--accent-green);
    }
    .payments-filters-body {
        padding: 1rem 1.25rem 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="looker-dashboard payments-index-page">
    <header class="looker-header">
        <div class="looker-header-title">
            <h1>Payments</h1>
            <p>Recorded revenue, filters, refunds, and voids — aligned with finance workflows.</p>
        </div>
        <div class="looker-actions flex-wrap">
            <a href="{{ route('finance.payments.create') }}" class="action-btn action-btn-primary">
                <i class="fas fa-plus" aria-hidden="true"></i> Record payment
            </a>
            <button type="button" class="action-btn action-btn-secondary" onclick="exportPayments()">
                <i class="fas fa-file-csv text-tertiary" aria-hidden="true"></i> Export CSV
            </button>
            <button type="button" class="action-btn action-btn-secondary" onclick="refreshPage()">
                <i class="fas fa-sync-alt text-tertiary" aria-hidden="true"></i> Refresh
            </button>
        </div>
    </header>

    <div class="kpi-wrapper">
        <div class="kpi-card-looker">
            <div class="kpi-top">
                <div class="kpi-title">Total revenue</div>
                <div class="kpi-icon-wrapper primary-icon"><i class="fas fa-dollar-sign" aria-hidden="true"></i></div>
            </di            <div class="kpi-value-looker">${{ number_format($stats['total_amount'] ?? 0, 2) }}</div>
            <div class="kpi-bottom trend-neutral">
                @if(isset($stats['total_product_amount']) && $stats['total_product_amount'] > 0)
                    <span title="Cash: ${{ number_format($stats['total_cash_amount'] ?? 0, 2) }} | Product Barter: ${{ number_format($stats['total_product_amount'] ?? 0, 2) }}">
                        <i class="fas fa-coins text-success"></i> ${{ number_format($stats['total_cash_amount'] ?? 0, 2) }} cash + <i class="fas fa-boxes text-info"></i> ${{ number_format($stats['total_product_amount'] ?? 0, 2) }} product
                    </span>
                @else
                    <i class="fas fa-fw fa-circle" style="font-size: 6px;" aria-hidden="true"></i> Completed payments only
                @endif
            </div>
        </div>
        <div class="kpi-card-looker success">
            <div class="kpi-top">
                <div class="kpi-title">Total payments</div>
                <div class="kpi-icon-wrapper success-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format($stats['total_payments'] ?? 0) }}</div>
            <div class="kpi-bottom trend-positive">
                <i class="fas fa-list" aria-hidden="true"></i> All statuses in ledger
            </div>
        </div>
        <div class="kpi-card-looker warning">
            <div class="kpi-top">
                <div class="kpi-title">Pending</div>
                <div class="kpi-icon-wrapper warning-icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format($stats['pending_payments'] ?? 0) }}</div>
            <div class="kpi-bottom trend-warning">
                <i class="fas fa-hourglass-half" aria-hidden="true"></i> Awaiting completion
            </div>
        </div>
        <div class="kpi-card-looker purple">
            <div class="kpi-top">
                <div class="kpi-title">Failed</div>
                <div class="kpi-icon-wrapper purple-icon"><i class="fas fa-times-circle" aria-hidden="true"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format($stats['failed_payments'] ?? 0) }}</div>
            <div class="kpi-bottom trend-neutral">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Needs follow-up
            </div>
        </div>
    </div>

    <div class="canvas-panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i class="fas fa-filter" aria-hidden="true"></i> Filters</h2>
            <button type="button" class="action-btn action-btn-secondary action-btn-icon" data-toggle="collapse" data-target="#paymentsFiltersCollapse" aria-expanded="true" aria-controls="paymentsFiltersCollapse" title="Toggle filters">
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
        </div>
        <div class="collapse show payments-filters-body" id="paymentsFiltersCollapse">
            <form method="GET" action="{{ route('finance.payments.index') }}" id="filterForm" class="container-fluid px-0">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="payments_search" class="form-label font-weight-bold">Search</label>
                        <input type="text" name="search" id="payments_search" class="form-control"
                                placeholder="Transaction ID, amount, client…"
                                value="{{ request('search') }}" autocomplete="off">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="payments_status" class="form-label font-weight-bold">Status</label>
                        <select name="status" id="payments_status" class="form-control">
                            <option value="">All status</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="payments_method" class="form-label font-weight-bold">Method</label>
                        <select name="payment_method" id="payments_method" class="form-control">
                            <option value="">All methods</option>
                            @foreach($paymentMethods ?? [] as $method)
                                <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                                    @if($method === 'split')
                                        Split (Money + Product)
                                    @elseif($method === 'product_exchange')
                                        Product Exchange Barter
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $method)) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div></div>
                    <div class="col-md-2 mb-3">
                        <label for="payments_date_from" class="form-label font-weight-bold">Date from</label>
                        <input type="date" name="date_from" id="payments_date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="payments_date_to" class="form-label font-weight-bold">Date to</label>
                        <input type="date" name="date_to" id="payments_date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="submit" class="action-btn action-btn-primary w-100 justify-content-center" title="Apply filters">
                            <i class="fas fa-search" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 d-flex flex-wrap align-items-center">
                        <a href="{{ route('finance.payments.index') }}" class="action-btn action-btn-secondary mr-2 mb-2">
                            <i class="fas fa-times" aria-hidden="true"></i> Clear filters
                        </a>
                        @if(request()->hasAny(['search', 'status', 'payment_method', 'date_from', 'date_to']))
                            <span class="status-badge status-badge-blue mb-2">{{ $payments->total() }} result(s)</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="canvas-panel hr-panel-flush">
        <div class="panel-header">
            <h2 class="panel-title"><i class="fas fa-list" aria-hidden="true"></i> Payment records</h2>
            <span class="status-badge status-badge-blue">{{ $payments->total() }} total</span>
        </div>
        <div class="looker-table-wrapper">
            <table class="looker-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="text-muted small font-weight-bold">#{{ $payment->id }}</td>
                        <td>
                            @if($payment->client)
                                <div class="d-flex align-items-center">
                                    <div class="mr-2">
                                        <x-avatar
                                            :avatar="$payment->client->avatar"
                                            :name="$payment->client->name"
                                            :size="'xs'"
                                            :type="'client'"
                                            :shape="'circle'"
                                        />
                                    </div>
                                    <div>
                                        <a href="{{ route('clients.show', $payment->client) }}" class="text-link-strong d-block">
                                            {{ $payment->client->company ?? 'N/A' }}
                                        </a>
                                        @if($payment->client->name && $payment->client->company)
                                            <small class="text-muted">{{ $payment->client->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td><span class="payments-amount-strong">${{ number_format($payment->amount, 2) }}</span></td>
                        <td>
                            @php
                                $method = $payment->payment_method;
                                $isSplit = method_exists($payment, 'isSplit') ? $payment->isSplit() : ($method === 'split');
                                $isProdExchange = method_exists($payment, 'isProductExchange') ? $payment->isProductExchange() : ($method === 'product_exchange');
                                
                                if ($isSplit) {
                                    $badgeClass = 'status-badge-purple';
                                    $methodIcon = 'layer-group';
                                    $methodLabel = 'Split Payment';
                                } elseif ($isProdExchange) {
                                    $badgeClass = 'status-badge-indigo';
                                    $methodIcon = 'boxes';
                                    $methodLabel = 'Product Exchange';
                                } else {
                                    $badgeClass = 'status-badge-blue';
                                    $methodIcon = $method == 'cash' ? 'money-bill-wave' : ($method == 'bank_transfer' ? 'university' : ($method == 'online' ? 'credit-card' : 'file-invoice'));
                                    $methodLabel = ucfirst(str_replace('_', ' ', $method));
                                }
                            @endphp
                            <span class="status-badge {{ $badgeClass }}">
                                <i class="fas fa-{{ $methodIcon }} mr-1" aria-hidden="true"></i>
                                {{ $methodLabel }}
                            </span>
                            @if($isSplit)
                                <div class="small text-muted mt-1" style="font-size: 0.75rem; line-height: 1.2;">
                                    <span class="text-success"><i class="fas fa-money-bill-wave"></i> ${{ number_format($payment->cash_amount ?? 0, 2) }}</span>
                                    <span class="mx-1">+</span>
                                    <span class="text-info" title="{{ $payment->product_details }}"><i class="fas fa-box-open"></i> ${{ number_format($payment->product_amount ?? 0, 2) }} prod</span>
                                </div>
                            @elseif($isProdExchange && $payment->product_details)
                                <div class="small text-muted mt-1 text-truncate" style="font-size: 0.75rem; max-width: 150px;" title="{{ $payment->product_details }}">
                                    <i class="fas fa-info-circle text-tertiary"></i> {{ $payment->product_details }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClass = [
                                    'completed' => 'status-badge-green',
                                    'pending' => 'status-badge-orange',
                                    'failed' => 'status-badge-red',
                                    'refunded' => 'status-badge-purple',
                                ];
                                $statusIcons = [
                                    'completed' => 'check-circle',
                                    'pending' => 'clock',
                                    'failed' => 'times-circle',
                                    'refunded' => 'undo',
                                ];
                                $sc = $statusClass[$payment->status] ?? 'status-badge-neutral';
                                $si = $statusIcons[$payment->status] ?? 'circle';
                            @endphp
                            <span class="status-badge {{ $sc }}">
                                <i class="fas fa-{{ $si }} mr-1" aria-hidden="true"></i>
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td>
                            @if($payment->paid_at)
                                <strong class="d-block">{{ $payment->paid_at->format('M d, Y') }}</strong>
                                <small class="text-muted">{{ $payment->paid_at->format('H:i') }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->user)
                                <span class="status-badge status-badge-neutral">{{ $payment->user->username }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="hr-table-actions">
                                <a href="{{ route('finance.payments.invoice', $payment->id) }}"
                                   class="action-btn action-btn-secondary action-btn-icon"
                                   target="_blank" rel="noopener noreferrer"
                                   title="View invoice"><i class="fas fa-file-invoice" aria-hidden="true"></i></a>
                                @if($payment->booking_id)
                                <a href="{{ route('books.show', $payment->booking_id) }}"
                                   class="action-btn action-btn-secondary action-btn-icon"
                                   title="View booking"><i class="fas fa-calendar-check" aria-hidden="true"></i></a>
                                @endif
                                @if($payment->client_id)
                                <a href="{{ route('clients.show', $payment->client_id) }}"
                                   class="action-btn action-btn-success-soft action-btn-icon"
                                   title="View client"><i class="fas fa-user" aria-hidden="true"></i></a>
                                @endif
                                @if($payment->status === \App\Models\Payment::STATUS_COMPLETED)
                                <button type="button"
                                        class="action-btn action-btn-secondary action-btn-icon"
                                        onclick="refundPayment({{ $payment->id }}, '{{ number_format($payment->amount, 2) }}')"
                                        title="Refund payment"><i class="fas fa-undo" aria-hidden="true"></i></button>
                                @endif
                                @if(in_array($payment->status, [\App\Models\Payment::STATUS_COMPLETED, \App\Models\Payment::STATUS_PENDING]))
                                <button type="button"
                                        class="action-btn action-btn-danger-soft action-btn-icon"
                                        onclick="voidPayment({{ $payment->id }})"
                                        title="Void payment"><i class="fas fa-ban" aria-hidden="true"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="hr-empty-cell">
                            <i class="fas fa-money-bill-wave-slash" aria-hidden="true"></i>
                            <p class="mb-2">No payments found</p>
                            <a href="{{ route('finance.payments.create') }}" class="action-btn action-btn-primary">
                                <i class="fas fa-plus" aria-hidden="true"></i> Record first payment
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($payments, 'hasPages') && $payments->hasPages())
        <div class="hr-panel-footer">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <span class="text-muted small">
                        @if($payments->firstItem())
                            Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}
                        @else
                            {{ $payments->total() }} payment(s) total
                        @endif
                    </span>
                </div>
                <div class="col-md-6 text-md-right">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '{{ route("export.bookings") }}?' + params.toString();
}

function refreshPage() {
    if (typeof showLoading === 'function') {
        showLoading();
    }
    setTimeout(() => {
        location.reload();
    }, 500);
}

function refundPayment(paymentId, amount) {
    Swal.fire({
        title: 'Refund Payment?',
        html: `Are you sure you want to refund payment #${paymentId}?<br><br><strong>Amount: $${amount}</strong><br><br>This will:<br>• Create a refund payment entry<br>• Revert booth status to confirmed<br>• Update payment status to refunded`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, refund it!',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Optional: Add refund reason...',
        inputAttributes: {
            'aria-label': 'Refund reason'
        },
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            return fetch(`/finance/payments/${paymentId}/refund`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    notes: notes || ''
                })
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
            if (result.value && result.value.success !== undefined) {
                Swal.fire('Refunded!', result.value.message || 'Payment has been refunded.', 'success')
                    .then(() => location.reload());
            } else {
                location.reload();
            }
        }
    });
}

function voidPayment(paymentId) {
    Swal.fire({
        title: 'Void Payment?',
        html: `Are you sure you want to void payment #${paymentId}?<br><br>This will:<br>• Mark payment as failed/voided<br>• Revert booth status if payment was completed<br><br><strong>This action cannot be undone!</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, void it!',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Optional: Add void reason...',
        inputAttributes: {
            'aria-label': 'Void reason'
        },
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            return fetch(`/finance/payments/${paymentId}/void`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    notes: notes || ''
                })
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
            if (result.value && result.value.success !== undefined) {
                Swal.fire('Voided!', result.value.message || 'Payment has been voided.', 'success')
                    .then(() => location.reload());
            } else {
                location.reload();
            }
        }
    });
}
</script>
@endpush
