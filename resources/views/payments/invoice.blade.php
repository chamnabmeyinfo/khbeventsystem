<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $payment->id }}</title>
    <link href="{{ asset('vendor/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        .invoice-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Invoice
            </button>
            <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Payments
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="invoice-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3>INVOICE</h3>
                            <p class="mb-0"><strong>Invoice #:</strong> {{ $payment->id }}</p>
                            <p class="mb-0"><strong>Date:</strong> {{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : $payment->created_at->format('Y-m-d') }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5>KHB Booth System</h5>
                            <p class="mb-0">Payment Receipt</p>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Bill To:</h6>
                        <p class="mb-0"><strong>{{ $payment->client->company ?? $payment->client->name }}</strong></p>
                        <p class="mb-0">{{ $payment->client->name }}</p>
                        @if($payment->client->phone_number)
                        <p class="mb-0">{{ $payment->client->phone_number }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6>Payment Details:</h6>
                        <p class="mb-0"><strong>Booking ID:</strong> #{{ $payment->booking_id }}</p>
                        <p class="mb-0"><strong>Payment Method:</strong> 
                            @if(method_exists($payment, 'isSplit') && $payment->isSplit())
                                Split (Money + Product Barter)
                            @elseif(method_exists($payment, 'isProductExchange') && $payment->isProductExchange())
                                Product Exchange Barter
                            @else
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            @endif
                        </p>
                        <p class="mb-0"><strong>Status:</strong> 
                            <span class="badge bg-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end" style="width: 180px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(method_exists($payment, 'isSplit') && $payment->isSplit())
                        <tr>
                            <td>
                                <div><strong>Cash / Bank Transfer Portion</strong></div>
                                <small class="text-muted">Settled via {{ ucfirst(str_replace('_', ' ', $payment->cash_payment_method ?? 'cash/transfer')) }}</small>
                            </td>
                            <td class="text-end">${{ number_format($payment->cash_amount ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>
                                <div><strong>Product Exchange Deduction</strong></div>
                                @if($payment->product_details)
                                <small class="text-muted">Exchange items: {{ $payment->product_details }}</small>
                                @endif
                            </td>
                            <td class="text-end">${{ number_format($payment->product_amount ?? 0, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Total Value Credited</strong></td>
                            <td class="text-end"><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                        @elseif(method_exists($payment, 'isProductExchange') && $payment->isProductExchange())
                        <tr>
                            <td>
                                <div><strong>Product Exchange Barter (100% Goods Deduction)</strong></div>
                                @if($payment->product_details)
                                <small class="text-muted">Exchange items: {{ $payment->product_details }}</small>
                                @endif
                            </td>
                            <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Total Value Credited</strong></td>
                            <td class="text-end"><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                        @else
                        <tr>
                            <td>Booth Booking Payment</td>
                            <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Total</strong></td>
                            <td class="text-end"><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                @if($payment->notes)
                <div class="mt-3">
                    <strong>Notes:</strong>
                    <p>{{ $payment->notes }}</p>
                </div>
                @endif

                <div class="mt-4 text-center">
                    <p class="text-muted">Thank you for your payment!</p>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap5/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>

