@extends('layouts.adminlte')

@section('title', 'Record Payment')
@section('page-title', 'Record Payment')
@section('breadcrumb', 'Finance / Payments / Create')

@push('styles')
<style>
    .payment-structure-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .payment-structure-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
        position: relative;
    }
    .payment-structure-card:hover {
        border-color: #3b82f6;
        background: #f8fafc;
    }
    .payment-structure-card.active {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
    }
    .payment-structure-card input[type="radio"] {
        position: absolute;
        top: 14px;
        right: 14px;
    }
    .payment-structure-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    .payment-structure-desc {
        font-size: 0.8rem;
        color: #64748b;
        margin: 0;
        line-height: 1.3;
    }
    .split-calc-summary {
        background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 16px;
        margin-top: 15px;
        margin-bottom: 20px;
    }
    .split-calc-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        font-size: 0.9rem;
    }
    .split-calc-row.total-row {
        border-top: 2px solid #94a3b8;
        padding-top: 10px;
        margin-top: 6px;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .badge-product-barter {
        background-color: #6366f1;
        color: #fff;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h3 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-money-bill-wave text-primary mr-2"></i>Record New Payment
            </h3>
        </div>
        <form action="{{ route('finance.payments.store') }}" method="POST" id="paymentForm">
            @csrf
            <div class="card-body">
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

                <!-- Booking Selector -->
                <div class="form-group mb-4">
                    <label for="booking_id" class="font-weight-bold">Select Booking <span class="text-danger">*</span></label>
                    <select name="booking_id" id="booking_id" class="form-control @error('booking_id') is-invalid @enderror" required>
                        <option value="">-- Choose a booking --</option>
                        @foreach(\App\Models\Book::with('client')->orderBy('date_book', 'desc')->orderBy('id', 'desc')->get() as $book)
                        @php
                            $bookTotal = $book->total_amount ?? $book->calculateTotalAmount();
                            $bookPaid = $book->paid_amount ?? 0;
                            $bookBalance = max(0, $bookTotal - $bookPaid);
                        @endphp
                        <option value="{{ $book->id }}" 
                            {{ old('booking_id', request('booking_id') ?? ($booking->id ?? '')) == $book->id ? 'selected' : '' }}
                            data-total="{{ $bookTotal }}"
                            data-paid="{{ $bookPaid }}"
                            data-balance="{{ $bookBalance }}"
                            data-client="{{ $book->client->company ?? $book->client->name ?? 'Client' }}">
                            #{{ $book->id }} - {{ $book->client->company ?? $book->client->name ?? 'N/A' }} ({{ $book->date_book ? $book->date_book->format('Y-m-d') : 'N/A' }}) - Total: ${{ number_format($bookTotal, 2) }}
                        </option>
                        @endforeach
                    </select>
                    @error('booking_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div id="bookingInfoContainer"></div>
                </div>

                <!-- Payment Structure Selector -->
                @php
                    $structure = old('payment_structure', 'standard');
                @endphp
                <div class="form-group mb-3">
                    <label class="font-weight-bold d-block">Payment Structure <span class="text-danger">*</span></label>
                    <div class="payment-structure-selector">
                        <label class="payment-structure-card {{ $structure === 'standard' ? 'active' : '' }}" id="cardStandard">
                            <input type="radio" name="payment_structure" value="standard" {{ $structure === 'standard' ? 'checked' : '' }} onchange="onStructureChange('standard')">
                            <div class="payment-structure-title">
                                <i class="fas fa-money-bill-wave text-success"></i> Standard Money
                            </div>
                            <p class="payment-structure-desc">100% paid in real cash, bank transfer, check, or online.</p>
                        </label>

                        <label class="payment-structure-card {{ $structure === 'split' ? 'active' : '' }}" id="cardSplit">
                            <input type="radio" name="payment_structure" value="split" {{ $structure === 'split' ? 'checked' : '' }} onchange="onStructureChange('split')">
                            <div class="payment-structure-title">
                                <i class="fas fa-layer-group text-primary"></i> Split Payment
                            </div>
                            <p class="payment-structure-desc">Part cash/bank transfer + Part product exchange deduction.</p>
                        </label>

                        <label class="payment-structure-card {{ $structure === 'product_exchange' ? 'active' : '' }}" id="cardProduct">
                            <input type="radio" name="payment_structure" value="product_exchange" {{ $structure === 'product_exchange' ? 'checked' : '' }} onchange="onStructureChange('product_exchange')">
                            <div class="payment-structure-title">
                                <i class="fas fa-box-open text-indigo"></i> 100% Product Exchange
                            </div>
                            <p class="payment-structure-desc">100% barter/goods deduction (sponsorship in-kind).</p>
                        </label>
                    </div>
                </div>

                <!-- SECTION 1: Standard Payment Fields -->
                <div id="standardFields" style="{{ $structure === 'standard' ? 'display: block;' : 'display: none;' }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount" class="font-weight-bold">Amount Received ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" 
                                           step="0.01" min="0.01" value="{{ old('amount') }}" placeholder="0.00">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="fillBalanceToAmount()">Full Balance</button>
                                    </div>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_method" class="font-weight-bold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="payment_method" class="form-control @error('payment_method') is-invalid @enderror">
                                    <option value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / ABA</option>
                                    <option value="online" {{ old('payment_method') == 'online' ? 'selected' : '' }}>Online Payment</option>
                                    <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Split Payment Fields -->
                <div id="splitFields" style="{{ $structure === 'split' ? 'display: block;' : 'display: none;' }}">
                    <div class="alert alert-light border mb-3">
                        <i class="fas fa-info-circle text-primary mr-1"></i>
                        <strong>Split Payment:</strong> Enter the real money received (cash/bank transfer) and the agreed value of products exchanged to deduct. Both sum up to the total credited against the booking.
                    </div>

                    <div class="row">
                        <!-- Left: Real Money Cash/Bank -->
                        <div class="col-md-6 border-right">
                            <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-dollar-sign mr-1"></i>1. Monetary Portion (Real Money)</h6>
                            <div class="form-group">
                                <label for="cash_amount" class="font-weight-bold">Cash / Bank Amount ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" name="cash_amount" id="cash_amount" class="form-control @error('cash_amount') is-invalid @enderror" 
                                           step="0.01" min="0.01" value="{{ old('cash_amount') }}" placeholder="e.g. 450.00" oninput="calculateSplitTotal()">
                                </div>
                                @error('cash_amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="cash_payment_method" class="font-weight-bold">Money Method <span class="text-danger">*</span></label>
                                <select name="cash_payment_method" id="cash_payment_method" class="form-control @error('cash_payment_method') is-invalid @enderror">
                                    <option value="bank_transfer" {{ old('cash_payment_method', 'bank_transfer') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / ABA</option>
                                    <option value="cash" {{ old('cash_payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="online" {{ old('cash_payment_method') == 'online' ? 'selected' : '' }}>Online Payment</option>
                                    <option value="check" {{ old('cash_payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                </select>
                                @error('cash_payment_method')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Right: Product Exchange Deduction -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-indigo mb-3"><i class="fas fa-box-open mr-1"></i>2. Product Exchange Deduction</h6>
                            <div class="form-group">
                                <label for="product_amount" class="font-weight-bold">Product Value to Deduct ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" name="product_amount" id="product_amount" class="form-control @error('product_amount') is-invalid @enderror" 
                                           step="0.01" min="0.01" value="{{ old('product_amount') }}" placeholder="e.g. 50.00" oninput="calculateSplitTotal()">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary btn-sm" type="button" onclick="autoFillProductDifference()">Fill Remainder</button>
                                    </div>
                                </div>
                                @error('product_amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="product_details" class="font-weight-bold">Product Items & Agreement Details <span class="text-danger">*</span></label>
                                <textarea name="product_details" id="product_details" class="form-control @error('product_details') is-invalid @enderror" rows="2" 
                                          placeholder="Describe items exchanged, quantities, or agreed barter terms (e.g. 50x Energy drink cases for VIP lounge)">{{ old('product_details') }}</textarea>
                                @error('product_details')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Split Live Calculation Box -->
                    <div class="split-calc-summary">
                        <div class="split-calc-row">
                            <span><i class="fas fa-money-bill text-success mr-2"></i>Real Money (Cash/Bank):</span>
                            <span class="font-weight-bold text-success" id="calcCashDisplay">$0.00</span>
                        </div>
                        <div class="split-calc-row">
                            <span><i class="fas fa-box text-indigo mr-2"></i>Product Exchange Deduction:</span>
                            <span class="font-weight-bold text-indigo" id="calcProductDisplay">$0.00</span>
                        </div>
                        <div class="split-calc-row total-row">
                            <span><i class="fas fa-check-circle text-primary mr-2"></i>Total Value Credited to Booking:</span>
                            <span class="text-primary" id="calcTotalDisplay">$0.00</span>
                        </div>
                        <div class="mt-2 text-muted small" id="calcComparisonNotice"></div>
                    </div>
                </div>

                <!-- SECTION 3: 100% Product Exchange Fields -->
                <div id="productOnlyFields" style="{{ $structure === 'product_exchange' ? 'display: block;' : 'display: none;' }}">
                    <div class="alert alert-light border mb-3">
                        <i class="fas fa-box-open text-indigo mr-1"></i>
                        <strong>100% Product Exchange:</strong> The entire payment value is settled via product/goods barter without cash.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_amount_only" class="font-weight-bold">Total Product Barter Value ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" id="product_amount_only" class="form-control" step="0.01" min="0.01" 
                                           value="{{ old('product_structure') === 'product_exchange' ? old('product_amount') : '' }}" 
                                           placeholder="e.g. 500.00" oninput="syncProductOnlyAmount(this.value)">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="fillBalanceToProductOnly()">Full Balance</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_details_only" class="font-weight-bold">Exchanged Products / Sponsorship Items <span class="text-danger">*</span></label>
                                <textarea id="product_details_only" class="form-control" rows="2" 
                                          placeholder="Specify products, quantities, delivery/agreement reference" oninput="syncProductOnlyDetails(this.value)">{{ old('product_structure') === 'product_exchange' ? old('product_details') : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Common Reference and Notes -->
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="transaction_id">Transaction / Reference ID</label>
                            <input type="text" name="transaction_id" id="transaction_id" class="form-control @error('transaction_id') is-invalid @enderror" 
                                   value="{{ old('transaction_id') }}" placeholder="Bank slip ref, receipt #, or barter agreement code">
                            @error('transaction_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional: Reference number for financial audit</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">Internal Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" 
                                      placeholder="Optional remarks, approvals, or receipt delivery notes">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light d-flex justify-content-between">
                <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times mr-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                    <i class="fas fa-save mr-1"></i>Record Payment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentBookingBalance = 0;
let currentBookingTotal = 0;

function onStructureChange(structure) {
    document.getElementById('cardStandard').classList.toggle('active', structure === 'standard');
    document.getElementById('cardSplit').classList.toggle('active', structure === 'split');
    document.getElementById('cardProduct').classList.toggle('active', structure === 'product_exchange');

    document.getElementById('standardFields').style.display = structure === 'standard' ? 'block' : 'none';
    document.getElementById('splitFields').style.display = structure === 'split' ? 'block' : 'none';
    document.getElementById('productOnlyFields').style.display = structure === 'product_exchange' ? 'block' : 'none';

    if (structure === 'split') {
        calculateSplitTotal();
    } else if (structure === 'product_exchange') {
        const productVal = document.getElementById('product_amount_only').value;
        if (productVal) {
            syncProductOnlyAmount(productVal);
        }
    }
}

function calculateSplitTotal() {
    const cash = parseFloat(document.getElementById('cash_amount').value) || 0;
    const prod = parseFloat(document.getElementById('product_amount').value) || 0;
    const total = cash + prod;

    document.getElementById('calcCashDisplay').textContent = `$${cash.toFixed(2)}`;
    document.getElementById('calcProductDisplay').textContent = `$${prod.toFixed(2)}`;
    document.getElementById('calcTotalDisplay').textContent = `$${total.toFixed(2)}`;

    const notice = document.getElementById('calcComparisonNotice');
    if (currentBookingBalance > 0) {
        if (Math.abs(total - currentBookingBalance) < 0.01) {
            notice.innerHTML = `<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Exactly matches outstanding booking balance ($${currentBookingBalance.toFixed(2)}). Booking will be fully PAID.</span>`;
        } else if (total < currentBookingBalance) {
            const rem = currentBookingBalance - total;
            notice.innerHTML = `<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Partial payment. Remaining booking balance will be <strong>$${rem.toFixed(2)}</strong>.</span>`;
        } else {
            const over = total - currentBookingBalance;
            notice.innerHTML = `<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Total exceeds booking balance by <strong>$${over.toFixed(2)}</strong>.</span>`;
        }
    } else {
        notice.textContent = '';
    }
}

function fillBalanceToAmount() {
    if (currentBookingBalance > 0) {
        document.getElementById('amount').value = currentBookingBalance.toFixed(2);
    }
}

function fillBalanceToProductOnly() {
    if (currentBookingBalance > 0) {
        const val = currentBookingBalance.toFixed(2);
        document.getElementById('product_amount_only').value = val;
        syncProductOnlyAmount(val);
    }
}

function syncProductOnlyAmount(val) {
    let hiddenInput = document.getElementById('product_amount_hidden');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'product_amount';
        hiddenInput.id = 'product_amount_hidden';
        document.getElementById('paymentForm').appendChild(hiddenInput);
    }
    hiddenInput.value = val;
}

function syncProductOnlyDetails(val) {
    let hiddenInput = document.getElementById('product_details_hidden');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'product_details';
        hiddenInput.id = 'product_details_hidden';
        document.getElementById('paymentForm').appendChild(hiddenInput);
    }
    hiddenInput.value = val;
}

function autoFillProductDifference() {
    const cash = parseFloat(document.getElementById('cash_amount').value) || 0;
    if (currentBookingBalance > cash) {
        const remainder = currentBookingBalance - cash;
        document.getElementById('product_amount').value = remainder.toFixed(2);
        calculateSplitTotal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const bookingSelect = document.getElementById('booking_id');

    function updateBookingCard() {
        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        const container = document.getElementById('bookingInfoContainer');

        if (selectedOption && selectedOption.value) {
            currentBookingBalance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
            currentBookingTotal = parseFloat(selectedOption.getAttribute('data-total')) || 0;
            const paid = parseFloat(selectedOption.getAttribute('data-paid')) || 0;

            container.innerHTML = `
                <div class="alert alert-info mt-2 mb-0 py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Total Amount</span>
                            <strong class="text-dark">$${currentBookingTotal.toFixed(2)}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Total Paid So Far</span>
                            <strong class="text-info">$${paid.toFixed(2)}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Current Balance Due</span>
                            <strong class="${currentBookingBalance > 0 ? 'text-warning font-weight-bold' : 'text-success'}">$${currentBookingBalance.toFixed(2)}</strong>
                        </div>
                    </div>
                </div>
            `;

            // Suggest in placeholder
            const amountInput = document.getElementById('amount');
            if (amountInput && !amountInput.value) {
                amountInput.placeholder = `Remaining balance: $${currentBookingBalance.toFixed(2)}`;
            }

            calculateSplitTotal();
        } else {
            currentBookingBalance = 0;
            currentBookingTotal = 0;
            container.innerHTML = '';
        }
    }

    bookingSelect?.addEventListener('change', updateBookingCard);

    if (bookingSelect && bookingSelect.value) {
        updateBookingCard();
    }

    // Initialize radio structure
    const checkedRadio = document.querySelector('input[name="payment_structure"]:checked');
    if (checkedRadio) {
        onStructureChange(checkedRadio.value);
    }
});
</script>
@endpush
