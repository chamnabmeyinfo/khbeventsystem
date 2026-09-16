@extends('layouts.adminlte')

@section('title', 'Edit Payment #' . $payment->id)
@section('page-title', 'Edit Payment')
@section('breadcrumb', 'Finance / Payments / Edit #' . $payment->id)

@push('styles')
<style>
    .payment-structure-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
        height: 100%;
    }
    .payment-structure-card:hover {
        border-color: #b0c4de;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .payment-structure-card.active {
        border-color: #007bff;
        background: #f0f7ff;
        box-shadow: 0 4px 14px rgba(0,123,255,0.15);
    }
    .payment-structure-card .custom-control {
        pointer-events: none;
    }
    .calc-breakdown-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #eef2f7 100%);
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 18px;
    }
    .calc-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        font-size: 0.95rem;
    }
    .calc-row.total-row {
        border-top: 2px dashed #cbd5e1;
        margin-top: 8px;
        padding-top: 10px;
        font-weight: 700;
        font-size: 1.15rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="card card-primary card-outline shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-edit text-primary mr-2"></i>Edit Payment #{{ $payment->id }}
            </h3>
            @if($payment->booking_id)
                <a href="{{ route('books.show', $payment->booking_id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-calendar-check mr-1"></i>View Booking #{{ $payment->booking_id }}
                </a>
            @endif
        </div>
        <form action="{{ route('finance.payments.update', $payment->id) }}" method="POST" id="editPaymentForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ request('redirect_to', $payment->booking_id ? 'booking' : 'index') }}">

            <div class="card-body">
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

                <!-- Linked Booking Information -->
                @if($payment->booking)
                <div class="alert alert-light border mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <span class="text-muted d-block small text-uppercase font-weight-bold">Booking</span>
                            <strong>#{{ $payment->booking->id }}</strong> 
                            <span class="text-muted">({{ $payment->booking->client ? ($payment->booking->client->company ?? $payment->booking->client->name) : 'No client' }})</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small text-uppercase font-weight-bold">Total Booking Price</span>
                            <strong class="text-dark">${{ number_format($payment->booking->total_amount, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small text-uppercase font-weight-bold">Current Remaining Balance</span>
                            <strong class="{{ $payment->booking->balance_amount > 0 ? 'text-warning' : 'text-success' }}">
                                ${{ number_format($payment->booking->balance_amount, 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
                @endif

                @php
                    $currentStructure = old('payment_structure', $payment->isSplit() ? 'split' : ($payment->isProductExchange() ? 'product_exchange' : 'standard'));
                @endphp

                <!-- Payment Structure Selection -->
                <div class="form-group mb-4">
                    <label class="font-weight-bold mb-2">
                        <i class="fas fa-layer-group text-primary mr-1"></i>Payment Structure <span class="text-danger">*</span>
                    </label>
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="payment-structure-card {{ $currentStructure === 'standard' ? 'active' : '' }}" id="cardStandard" onclick="onStructureChange('standard')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="structure_standard" name="payment_structure" value="standard" class="custom-control-input" {{ $currentStructure === 'standard' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="structure_standard">
                                        <i class="fas fa-money-bill-wave text-success mr-1"></i>Standard Money
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 mt-2 pl-4">
                                    100% Cash, Bank Transfer, Online, or Check.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="payment-structure-card {{ $currentStructure === 'split' ? 'active' : '' }}" id="cardSplit" onclick="onStructureChange('split')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="structure_split" name="payment_structure" value="split" class="custom-control-input" {{ $currentStructure === 'split' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="structure_split">
                                        <i class="fas fa-balance-scale text-primary mr-1"></i>Split (Money + Product)
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 mt-2 pl-4">
                                    Part in cash/bank transfer and part as product exchange deduction.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="payment-structure-card {{ $currentStructure === 'product_exchange' ? 'active' : '' }}" id="cardProduct" onclick="onStructureChange('product_exchange')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="structure_product" name="payment_structure" value="product_exchange" class="custom-control-input" {{ $currentStructure === 'product_exchange' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="structure_product">
                                        <i class="fas fa-boxes text-info mr-1"></i>100% Product Exchange
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 mt-2 pl-4">
                                    100% Barter / goods exchange deducting the full booth balance.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Fields Container -->
                <div class="row">
                    <!-- Standard Amount -->
                    <div class="col-md-6" id="wrapperStandardAmount" style="{{ $currentStructure === 'standard' ? '' : 'display: none;' }}">
                        <div class="form-group">
                            <label for="amount" class="font-weight-bold">Total Payment Amount ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold">$</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" name="amount" id="amount" 
                                       class="form-control form-control-lg @error('amount') is-invalid @enderror" 
                                       placeholder="0.00" value="{{ old('amount', $payment->amount) }}" oninput="updateLiveCalc()">
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Split Amounts -->
                    <div class="col-md-6" id="wrapperCashAmount" style="{{ $currentStructure === 'split' ? '' : 'display: none;' }}">
                        <div class="form-group">
                            <label for="cash_amount" class="font-weight-bold text-success">
                                <i class="fas fa-coins mr-1"></i>Cash / Transfer Portion ($) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold text-success">$</span>
                                </div>
                                <input type="number" step="0.01" min="0" name="cash_amount" id="cash_amount" 
                                       class="form-control form-control-lg @error('cash_amount') is-invalid @enderror" 
                                       placeholder="0.00" value="{{ old('cash_amount', $payment->cash_amount ?? ($payment->isSplit() ? 0 : $payment->amount)) }}" oninput="updateLiveCalc()">
                            </div>
                            @error('cash_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Product Quantity and Unit Price -->
                    <div class="col-md-6" id="wrapperProductCount" style="{{ in_array($currentStructure, ['split', 'product_exchange']) ? '' : 'display: none;' }}">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="product_quantity" class="font-weight-bold text-info">
                                        <i class="fas fa-cubes mr-1"></i>Product Count / Qty
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="1" min="0" name="product_quantity" id="product_quantity" 
                                               class="form-control form-control-lg @error('product_quantity') is-invalid @enderror" 
                                               placeholder="e.g. 10" value="{{ old('product_quantity', $payment->product_quantity) }}" oninput="calculateEditProductSubtotal()">
                                        <div class="input-group-append"><span class="input-group-text">units</span></div>
                                    </div>
                                    @error('product_quantity')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="product_unit_price" class="font-weight-bold text-info">
                                        <i class="fas fa-tag mr-1"></i>Price per Unit ($)
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" step="0.01" min="0" name="product_unit_price" id="product_unit_price" 
                                               class="form-control form-control-lg @error('product_unit_price') is-invalid @enderror" 
                                               placeholder="e.g. 5.00" value="{{ old('product_unit_price', $payment->product_unit_price) }}" oninput="calculateEditProductSubtotal()">
                                        <div class="input-group-append"><span class="input-group-text">/u</span></div>
                                    </div>
                                    @error('product_unit_price')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6" id="wrapperProductAmount" style="{{ in_array($currentStructure, ['split', 'product_exchange']) ? '' : 'display: none;' }}">
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="product_amount" class="font-weight-bold text-info mb-0">
                                    <i class="fas fa-box-open mr-1"></i>Product Exchange Deduction ($) <span class="text-danger">*</span>
                                </label>
                                @if($payment->booking)
                                <div>
                                    <button class="btn btn-outline-success btn-xs font-weight-bold mr-1" type="button" onclick="autoCalcEditCashFromBooth()" title="Auto-calculate cash needed to settle booth">
                                        <i class="fas fa-coins mr-1"></i>Auto Cash
                                    </button>
                                    <button class="btn btn-outline-info btn-xs font-weight-bold" type="button" onclick="autoCalcEditCountFromBooth()" title="Auto-calculate product count from unit price">
                                        <i class="fas fa-calculator mr-1"></i>Auto Count
                                    </button>
                                </div>
                                @endif
                            </div>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold text-info">$</span>
                                </div>
                                <input type="number" step="0.01" min="0" name="product_amount" id="product_amount" 
                                       class="form-control form-control-lg font-weight-bold text-info @error('product_amount') is-invalid @enderror" 
                                       placeholder="0.00" value="{{ old('product_amount', $payment->product_amount ?? ($payment->isProductExchange() ? $payment->amount : 0)) }}" oninput="updateLiveCalc()">
                            </div>
                            @error('product_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-6" id="wrapperPaymentMethod" style="{{ $currentStructure === 'product_exchange' ? 'display: none;' : '' }}">
                        <div class="form-group">
                            <label for="payment_method" class="font-weight-bold" id="lblPaymentMethod">
                                {{ $currentStructure === 'split' ? 'Cash/Transfer Method' : 'Payment Method' }} <span class="text-danger">*</span>
                            </label>
                            <select name="payment_method" id="payment_method" class="form-control form-control-lg @error('payment_method') is-invalid @enderror" required>
                                <option value="cash" {{ old('payment_method', $payment->cash_payment_method ?? $payment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ old('payment_method', $payment->cash_payment_method ?? $payment->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="online" {{ old('payment_method', $payment->cash_payment_method ?? $payment->payment_method) == 'online' ? 'selected' : '' }}>Online Payment</option>
                                <option value="check" {{ old('payment_method', $payment->cash_payment_method ?? $payment->payment_method) == 'check' ? 'selected' : '' }}>Check</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Product Exchange Details -->
                <div class="form-group mb-4" id="wrapperProductDetails" style="{{ in_array($currentStructure, ['split', 'product_exchange']) ? '' : 'display: none;' }}">
                    <label for="product_details" class="font-weight-bold text-info">
                        <i class="fas fa-clipboard-list mr-1"></i>Product Exchange Terms & Items
                    </label>
                    <textarea name="product_details" id="product_details" rows="2" 
                              class="form-control @error('product_details') is-invalid @enderror" 
                              placeholder="Describe goods received for barter deduction (e.g., 50x promotional packages, brand gift hampers, goods invoice #)">{{ old('product_details', $payment->product_details) }}</textarea>
                    @error('product_details')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Live Calculation Preview Card -->
                <div class="calc-breakdown-card mb-4" id="calcBreakdownCard">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-weight-bold text-dark mb-0">
                            <i class="fas fa-calculator text-primary mr-1"></i>Credited Payment Summary
                        </h6>
                    </div>
                    <div class="calc-row" id="rowCashPart" style="{{ $currentStructure === 'split' ? '' : 'display: none;' }}">
                        <span class="text-muted"><i class="fas fa-coins text-success mr-1"></i>Cash / Transfer Portion:</span>
                        <strong class="text-success" id="calcCashPortion">$0.00</strong>
                    </div>
                    <div class="calc-row" id="rowProductPart" style="{{ in_array($currentStructure, ['split', 'product_exchange']) ? '' : 'display: none;' }}">
                        <span class="text-muted"><i class="fas fa-boxes text-info mr-1"></i>Product Exchange Deduction:</span>
                        <span class="text-info font-weight-bold">
                            <span id="calcProductPortion">$0.00</span>
                            <span class="badge badge-info ml-1" id="calcProductCountBadge" style="display: none;"></span>
                        </span>
                    </div>
                    <div class="calc-row total-row">
                        <span class="text-dark">Total Credited Payment:</span>
                        <span class="text-primary font-weight-bold" id="calcTotalCredited" style="font-size: 1.35rem;">$0.00</span>
                    </div>
                </div>

                <!-- Status, Date, Transaction ID & Notes -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status" class="font-weight-bold">Payment Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="completed" {{ old('status', $payment->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="pending" {{ old('status', $payment->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="failed" {{ old('status', $payment->status) == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="refunded" {{ old('status', $payment->status) == 'refunded' ? 'selected' : '' }}>Refunded</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="paid_at" class="font-weight-bold">Payment Date & Time</label>
                            <input type="datetime-local" name="paid_at" id="paid_at" 
                                   class="form-control @error('paid_at') is-invalid @enderror" 
                                   value="{{ old('paid_at', $payment->paid_at ? $payment->paid_at->format('Y-m-d\TH:i') : '') }}">
                            @error('paid_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="transaction_id">Transaction / Reference ID</label>
                            <input type="text" name="transaction_id" id="transaction_id" 
                                   class="form-control @error('transaction_id') is-invalid @enderror" 
                                   placeholder="e.g. TXN-12345 or Check #" value="{{ old('transaction_id', $payment->transaction_id) }}">
                            @error('transaction_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="notes">Internal Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" 
                                      placeholder="Optional remarks, approvals, or receipt delivery notes">{{ old('notes', $payment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light d-flex justify-content-between">
                @if($payment->booking_id)
                    <a href="{{ route('books.show', $payment->booking_id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Booking #{{ $payment->booking_id }}
                    </a>
                @else
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </a>
                @endif
                <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                    <i class="fas fa-save mr-1"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function onStructureChange(structure) {
    document.getElementById('cardStandard').classList.toggle('active', structure === 'standard');
    document.getElementById('cardSplit').classList.toggle('active', structure === 'split');
    document.getElementById('cardProduct').classList.toggle('active', structure === 'product_exchange');
    
    document.getElementById('structure_standard').checked = (structure === 'standard');
    document.getElementById('structure_split').checked = (structure === 'split');
    document.getElementById('structure_product').checked = (structure === 'product_exchange');

    const wrapperStandard = document.getElementById('wrapperStandardAmount');
    const wrapperCash = document.getElementById('wrapperCashAmount');
    const wrapperProduct = document.getElementById('wrapperProductAmount');
    const wrapperProductCount = document.getElementById('wrapperProductCount');
    const wrapperPaymentMethod = document.getElementById('wrapperPaymentMethod');
    const wrapperProductDetails = document.getElementById('wrapperProductDetails');
    const lblPaymentMethod = document.getElementById('lblPaymentMethod');
    const rowCashPart = document.getElementById('rowCashPart');
    const rowProductPart = document.getElementById('rowProductPart');

    if (structure === 'standard') {
        wrapperStandard.style.display = '';
        wrapperCash.style.display = 'none';
        wrapperProduct.style.display = 'none';
        if (wrapperProductCount) wrapperProductCount.style.display = 'none';
        wrapperPaymentMethod.style.display = '';
        wrapperProductDetails.style.display = 'none';
        lblPaymentMethod.textContent = 'Payment Method';
        rowCashPart.style.display = 'none';
        rowProductPart.style.display = 'none';
    } else if (structure === 'split') {
        wrapperStandard.style.display = 'none';
        wrapperCash.style.display = '';
        wrapperProduct.style.display = '';
        if (wrapperProductCount) wrapperProductCount.style.display = '';
        wrapperPaymentMethod.style.display = '';
        wrapperProductDetails.style.display = '';
        lblPaymentMethod.textContent = 'Cash/Transfer Method';
        rowCashPart.style.display = '';
        rowProductPart.style.display = '';
    } else if (structure === 'product_exchange') {
        wrapperStandard.style.display = 'none';
        wrapperCash.style.display = 'none';
        wrapperProduct.style.display = '';
        if (wrapperProductCount) wrapperProductCount.style.display = '';
        wrapperPaymentMethod.style.display = 'none';
        wrapperProductDetails.style.display = '';
        rowCashPart.style.display = 'none';
        rowProductPart.style.display = '';
    }

    updateLiveCalc();
}

const bookingTotal = {{ $payment->booking ? (float)$payment->booking->total_amount : 0 }};
const bookingBalance = {{ $payment->booking ? (float)$payment->booking->balance_amount : 0 }};
const currentPaymentAmount = {{ (float)$payment->amount }};

function calculateEditProductSubtotal() {
    const qty = parseFloat(document.getElementById('product_quantity')?.value) || 0;
    const unitPrice = parseFloat(document.getElementById('product_unit_price')?.value) || 0;
    if (qty > 0 && unitPrice > 0) {
        document.getElementById('product_amount').value = (qty * unitPrice).toFixed(2);
    }
    updateLiveCalc();
}

function autoCalcEditCashFromBooth() {
    const target = bookingTotal > 0 ? bookingTotal : (bookingBalance + currentPaymentAmount);
    const prod = parseFloat(document.getElementById('product_amount')?.value) || 0;
    if (target > 0) {
        const cashNeeded = Math.max(0, target - prod);
        document.getElementById('cash_amount').value = cashNeeded.toFixed(2);
        updateLiveCalc();
    }
}

function autoCalcEditCountFromBooth() {
    const target = bookingTotal > 0 ? bookingTotal : (bookingBalance + currentPaymentAmount);
    const cash = parseFloat(document.getElementById('cash_amount')?.value) || 0;
    const unitPrice = parseFloat(document.getElementById('product_unit_price')?.value) || 0;
    const neededProduct = Math.max(0, target - cash);

    if (unitPrice > 0) {
        const count = Math.ceil(neededProduct / unitPrice);
        document.getElementById('product_quantity').value = count;
        document.getElementById('product_amount').value = (count * unitPrice).toFixed(2);
    } else {
        document.getElementById('product_amount').value = neededProduct.toFixed(2);
    }
    updateLiveCalc();
}

function updateLiveCalc() {
    const isStandard = document.getElementById('structure_standard').checked;
    const isSplit = document.getElementById('structure_split').checked;
    const isProduct = document.getElementById('structure_product').checked;

    let cash = 0;
    let product = 0;
    let total = 0;

    if (isStandard) {
        cash = parseFloat(document.getElementById('amount').value) || 0;
        total = cash;
    } else if (isSplit) {
        cash = parseFloat(document.getElementById('cash_amount').value) || 0;
        product = parseFloat(document.getElementById('product_amount').value) || 0;
        total = cash + product;
        const amtInput = document.getElementById('amount');
        if (amtInput) amtInput.value = total.toFixed(2);
    } else if (isProduct) {
        product = parseFloat(document.getElementById('product_amount').value) || 0;
        total = product;
        const amtInput = document.getElementById('amount');
        if (amtInput) amtInput.value = total.toFixed(2);
    }

    document.getElementById('calcCashPortion').textContent = '$' + cash.toFixed(2);
    document.getElementById('calcProductPortion').textContent = '$' + product.toFixed(2);
    document.getElementById('calcTotalCredited').textContent = '$' + total.toFixed(2);

    const qty = parseFloat(document.getElementById('product_quantity')?.value) || 0;
    const unitPrice = parseFloat(document.getElementById('product_unit_price')?.value) || 0;
    const countBadge = document.getElementById('calcProductCountBadge');
    if (countBadge) {
        if (qty > 0) {
            countBadge.style.display = 'inline-block';
            countBadge.textContent = unitPrice > 0 
                ? `${qty} units @ $${unitPrice.toFixed(2)}` 
                : `${qty} units`;
        } else {
            countBadge.style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateLiveCalc();
});
</script>
@endpush
