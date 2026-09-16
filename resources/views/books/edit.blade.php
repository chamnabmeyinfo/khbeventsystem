@extends('layouts.admin')

@section('title', 'Edit Booking')
@section('page-title', 'Edit Booking')
@section('breadcrumb', 'Bookings / Edit')


@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-looker.css') }}?v=3.6">
<style>
    .looker-dashboard { padding: 0 !important; }
    .glass-card {
        background: rgba(255, 255, 255, 0.45);
        backdrop-filter: blur(40px) saturate(180%);
        -webkit-backdrop-filter: blur(40px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 24px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        margin-bottom: 24px;
        transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }
    .glass-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.55);
        box-shadow: 0 15px 45px rgba(31, 38, 135, 0.2);
    }
    .kpi-card-looker {
        margin-bottom: 24px;
    }
</style>
@endpush

@push('body-class', 'ios-dashboard-mode')


@section('content')
<div class='looker-dashboard'>
<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Bookings</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.show', $book) }}">Booking #{{ $book->id }}</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>

    <div class="card card-warning card-outline">
        <div class="p-4 border-bottom">
            <h3 class="h5 fw-bold mb-0 text-dark"><i class="fas fa-edit mr-2"></i>Edit Booking #{{ $book->id }}</h3>
            <div class="card-tools">
                <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Booking
                </a>
            </div>
        </div>
        <form action="{{ route('books.update', $book) }}" method="POST" id="bookingForm">
            @csrf
            @method('PUT')
            <div class="p-4">
                <!-- General Error Display -->
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="fas fa-exclamation-triangle mr-2"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <!-- Client Selection -->
                <div class="form-section">
                    <h6><i class="fas fa-building mr-2"></i>Client Information</h6>
                    <div class="row">
                        <div class="col-md-8">
                            <label for="clientid" class="form-label">Select Client <span class="text-danger">*</span></label>
                            <select class="form-control @error('clientid') is-invalid @enderror" 
                                    id="clientid" name="clientid" required>
                                <option value="">Search or select a client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ (old('clientid', $book->clientid) == $client->id) ? 'selected' : '' }}>
                                        {{ $client->company ?? $client->name }} 
                                        @if($client->company && $client->name) - {{ $client->name }} @endif
                                        @if($client->phone_number) ({{ $client->phone_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('clientid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <div>
                                <a href="{{ route('clients.create') }}" class="btn btn-success btn-block" target="_blank">
                                    <i class="fas fa-plus mr-1"></i>New Client
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="form-section">
                    <h6><i class="fas fa-calendar-alt mr-2"></i>Booking Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_book" class="form-label">Booking Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" 
                                       class="form-control @error('date_book') is-invalid @enderror" 
                                       id="date_book" 
                                       name="date_book" 
                                       value="{{ old('date_book', $book->date_book ? \Carbon\Carbon::parse($book->date_book)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" 
                                       required>
                                @error('date_book')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type" class="form-label">Booking Type</label>
                                <select class="form-control @error('type') is-invalid @enderror" id="type" name="type">
                                    <option value="1" {{ old('type', $book->type ?? 1) == 1 ? 'selected' : '' }}>Regular</option>
                                    <option value="2" {{ old('type', $book->type) == 2 ? 'selected' : '' }}>Special</option>
                                    <option value="3" {{ old('type', $book->type) == 3 ? 'selected' : '' }}>Temporary</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Select the type of booking</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="form-label">Booking Status</label>
                                @php
                                    try {
                                        $statusSettings = \App\Models\BookingStatusSetting::getActiveStatuses();
                                    } catch (\Exception $e) {
                                        $statusSettings = collect([]);
                                    }
                                @endphp
                                <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                    @if($statusSettings && $statusSettings->count() > 0)
                                        @foreach($statusSettings as $status)
                                            <option value="{{ $status->status_code }}" 
                                                {{ old('status', $book->status ?? 1) == $status->status_code ? 'selected' : '' }}>
                                                {{ $status->status_name }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="1" {{ old('status', $book->status ?? 1) == 1 ? 'selected' : '' }}>Pending</option>
                                        <option value="2" {{ old('status', $book->status ?? 1) == 2 ? 'selected' : '' }}>Confirmed</option>
                                        <option value="3" {{ old('status', $book->status ?? 1) == 3 ? 'selected' : '' }}>Reserved</option>
                                        <option value="4" {{ old('status', $book->status ?? 1) == 4 ? 'selected' : '' }}>Paid</option>
                                    @endif
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Current status of this booking</small>
                            </div>
                        </div>
                        @if(auth()->user()->isAdmin() && isset($users) && $users->count() > 0)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="userid" class="form-label">
                                    <i class="fas fa-user-edit mr-1 text-primary"></i>Booked By (Team Member)
                                    <span class="badge badge-primary ms-1" style="font-size: 10px;">Super Admin</span>
                                </label>
                                <select class="form-control @error('userid') is-invalid @enderror" id="userid" name="userid">
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ (string) old('userid', $book->userid) === (string) $u->id ? 'selected' : '' }}>
                                            {{ $u->username }} ({{ $u->isAdmin() ? 'Admin' : ($u->role ? $u->role->name : 'Staff') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('userid')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Reassign ownership of this booking and linked booths to another team member</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_due_date" class="form-label">Payment Due Date</label>
                                <input type="date" 
                                       class="form-control @error('payment_due_date') is-invalid @enderror" 
                                       id="payment_due_date" 
                                       name="payment_due_date" 
                                       value="{{ old('payment_due_date', $book->payment_due_date ? $book->payment_due_date->format('Y-m-d') : '') }}">
                                @error('payment_due_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">When payment is due for this booking</small>
                            </div>
                        </div>
                        @else
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_due_date" class="form-label">Payment Due Date</label>
                                <input type="date" 
                                       class="form-control @error('payment_due_date') is-invalid @enderror" 
                                       id="payment_due_date" 
                                       name="payment_due_date" 
                                       value="{{ old('payment_due_date', $book->payment_due_date ? $book->payment_due_date->format('Y-m-d') : '') }}">
                                @error('payment_due_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">When payment is due for this booking</small>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="3" 
                                          placeholder="Add any additional notes about this booking...">{{ old('notes', $book->notes ?? '') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booth Selection -->
                <div class="form-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-cube mr-2"></i>Select Booths <span class="text-danger">*</span></h6>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="selectAllBooths()">
                                <i class="fas fa-check-double mr-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearAllBooths()">
                                <i class="fas fa-times mr-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                    
                    @error('booth_ids')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="booth-selector p-3 border rounded">
                                @forelse($allBooths as $booth)
                                    @php
                                        $isSelected = in_array($booth->id, old('booth_ids', $boothIds ?? []));
                                        $isReserved = !in_array($booth->status, [\App\Models\Booth::STATUS_AVAILABLE, \App\Models\Booth::STATUS_HIDDEN]);
                                        $isCurrentBooth = in_array($booth->id, $boothIds ?? []);
                                        $isDisabled = $isReserved && !$isCurrentBooth;
                                    @endphp
                                    <div class="booth-option p-2 mb-2 border rounded {{ $isSelected ? 'selected' : '' }} {{ $isDisabled ? 'opacity-50' : '' }}" 
                                         onclick="{{ !$isDisabled ? "toggleBooth({$booth->id})" : '' }}">
                                        <input type="checkbox" 
                                               name="booth_ids[]" 
                                               value="{{ $booth->id }}" 
                                               id="booth_{{ $booth->id }}"
                                               {{ $isSelected ? 'checked' : '' }}
                                               {{ $isDisabled ? 'disabled' : '' }}
                                               onchange="updateSelectedCount()">
                                        <label for="booth_{{ $booth->id }}" style="cursor: pointer; margin-bottom: 0;">
                                            <strong>{{ $booth->booth_number }}</strong>
                                            @if($booth->price)
                                                - ${{ number_format($booth->price, 2) }}
                                            @endif
                                            <span class="badge badge-{{ $booth->getStatusColor() }}">
                                                {{ $booth->getStatusLabel() }}
                                            </span>
                                            @if($isDisabled && !$isCurrentBooth)
                                                <small class="text-danger">(Reserved)</small>
                                            @endif
                                        </label>
                                    </div>
                                @empty
                                    <div class="alert alert-info">No booths available</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="selected-booths-summary">
                                <h6><i class="fas fa-list mr-2"></i>Selected Booths (<span id="selectedCount">0</span>)</h6>
                                <div id="selectedBoothsList" class="mt-2" style="max-height: 300px; overflow-y: auto;">
                                    <p class="text-muted small">No booths selected</p>
                                </div>
                                <div class="mt-3">
                                    <strong>Total Amount: $<span id="totalAmount">0.00</span></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-3 border-top bg-transparent">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>Update Booking
                </button>
                <a href="{{ route('books.show', $book) }}" class="btn btn-secondary">
                    <i class="fas fa-times mr-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
// Initialize selected booths count and list
let allBooths = @json($allBooths->keyBy('id'));
let selectedBooths = @json(old('booth_ids', $boothIds ?? []));

function toggleBooth(boothId) {
    const checkbox = document.getElementById('booth_' + boothId);
    if (checkbox && !checkbox.disabled) {
        checkbox.checked = !checkbox.checked;
        updateSelectedCount();
    }
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('input[name="booth_ids[]"]:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    
    // Update selected booths list
    const listDiv = document.getElementById('selectedBoothsList');
    if (count === 0) {
        listDiv.innerHTML = '<p class="text-muted small">No booths selected</p>';
        document.getElementById('totalAmount').textContent = '0.00';
    } else {
        let html = '<ul class="list-unstyled mb-0">';
        let total = 0;
        checkboxes.forEach(cb => {
            const boothId = parseInt(cb.value);
            const booth = allBooths[boothId];
            if (booth) {
                const price = parseFloat(booth.price || 0);
                total += price;
                html += `<li class="mb-1">
                    <i class="fas fa-cube text-primary mr-1"></i>
                    <strong>${booth.booth_number}</strong>
                    ${price > 0 ? ` - $${price.toFixed(2)}` : ''}
                </li>`;
            }
        });
        html += '</ul>';
        listDiv.innerHTML = html;
        document.getElementById('totalAmount').textContent = total.toFixed(2);
    }
}

function selectAllBooths() {
    document.querySelectorAll('input[name="booth_ids[]"]:not(:disabled)').forEach(cb => {
        cb.checked = true;
    });
    updateSelectedCount();
}

function clearAllBooths() {
    document.querySelectorAll('input[name="booth_ids[]"]:not(:disabled)').forEach(cb => {
        cb.checked = false;
    });
    updateSelectedCount();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});

// Form validation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const selectedBooths = document.querySelectorAll('input[name="booth_ids[]"]:checked');
    if (selectedBooths.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'No Booths Selected',
            text: 'Please select at least one booth for this booking.',
            confirmButtonColor: '#007bff'
        });
        return false;
    }
    showLoading();
});
</script>
@endpush

