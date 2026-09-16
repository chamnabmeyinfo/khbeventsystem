<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
       font-family: Arial, sans-serif;
          line-height: 1.6;
       color: #333;
            max-width: 600px;
            margin: 0 auto;
         padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .header p {
       margin: 0;
          font-size: 16px;
            opacity: 0.95;
        }
      .receipt-number {
            background: rgba(255,255,255,0.2);
       display: inline-block;
            padding: 8px 20px;
          border-radius: 20px;
            margin-top: 15px;
            font-weight: 600;
            font-size: 18px;
        }
      .content {
            padding: 30px;
        }
        .success-badge {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
          padding: 15px;
            border-radius: 8px;
        text-align: center;
            margin: 20px 0;
            font-weight: 600;
        }
        .payment-summary {
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border: 2px solid #007bff;
        }
        .amount-paid {
      text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
       margin: 15px 0;
    }
        .amount-paid .label {
            font-size: 14px;
      color: #666;
            margin-bottom: 5px;
        }
        .amount-paid .amount {
            font-size: 36px;
            font-weight: 700;
            color: #28a745;
            margin: 10px 0;
        }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
         border-radius: 8px;
            margin: 20px 0;
        border-left: 4px solid #007bff;
        }
        .info-box h3 {
        margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }
        .info-row {
          display: flex;
            justify-content: space-between;
            padding: 10px 0;
         border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
          border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #666;
        }
        .value {
            color: #333;
            text-align: right;
        }
        .transaction-id {
            background: #fff3cd;
            border: 1px solid #ffc107;
        padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            text-align: center;
            font-size: 14px;
        }
        .footer {
      background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
     .footer p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 14px 35px;
            background: #007bff;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .divider {
         border-top: 2px dashed #dee2e6;
          margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            body {
          padding: 10px;
            }
            .content {
                padding: 20px;
          }
            .header {
            padding: 30px 20px;
    }
            .info-row {
                flex-direction: column;
            }
            .value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>💳 Payment Receipt</h1>
        <p>Thank you for your payment</p>
          <div class="receipt-number">{{ $receiptNumber }}</div>
        </div>
        
        <div class="content">
          <p>Dear <strong>{{ $client->name ?? 'Valued Customer' }}</strong>,</p>
            
         <div class="success-badge">
                ✅ Payment Received Successfully
            </div>
       
            <p>This email confirms that we have received your payment. Below are the details of your transaction:</p>
            
            <!-- Payment Amount -->
          <div class="payment-summary">
                <div class="amount-paid">
               <div class="label">Amount Paid</div>
              <div class="amount">${{ number_format($amount, 2) }}</div>
                 <div class="label" style="color: #28a745;">✓ Payment Completed</div>
                </div>
            
             @if($transactionId)
                <div class="transaction-id">
          <strong>Transaction ID:</strong> {{ $transactionId }}
                </div>
                @endif
            </div>
            
            <!-- Payment Details -->
            <div class="info-box">
                <h3>💰 Payment Information</h3>
              <div class="info-row">
         <span class="label">Receipt Number:</span>
                <span class="value"><strong>{{ $receiptNumber }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="label">Payment Date:</span>
                    <span class="value">{{ $paymentDate->format('F d, Y g:i A') }}</span>
           </div>
           <div class="info-row">
         <span class="label">Payment Method:</span>
                    <span class="value">{{ $paymentMethod }}</span>
              </div>
                <div class="info-row">
             <span class="label">Amount:</span>
                    <span class="value"><strong style="color: #28a745;">${{ number_format($amount, 2) }}</strong></span>
                </div>
                <div class="info-row">
                <span class="label">Status:</span>
                 <span class="value"><strong style="color: #28a745;">✓ Completed</strong></span>
                </div>
            </div>
            
            <!-- Split / Product Exchange Details -->
            @if(!empty($isSplit))
            <div class="info-box" style="border-left-color: #6f42c1; background-color: #fcfaff;">
                <h3 style="color: #6f42c1;">📦 Payment Method Breakdown</h3>
                <div class="info-row">
                    <span class="label">Cash / Bank Transfer Portion:</span>
                    <span class="value"><strong style="color: #28a745;">${{ number_format($cashAmount ?? 0, 2) }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="label">Product Exchange Deduction:</span>
                    <span class="value">
                        <strong style="color: #17a2b8;">${{ number_format($productAmount ?? 0, 2) }}</strong>
                        @if(!empty($productQuantity))
                            <span style="display: inline-block; background-color: #17a2b8; color: #ffffff; font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">
                                {{ number_format($productQuantity) }} units
                                @if(!empty($productUnitPrice))
                                    @ ${{ number_format($productUnitPrice, 2) }}/u
                                @endif
                            </span>
                        @endif
                    </span>
                </div>
                @if(!empty($productDetails))
                <div class="info-row">
                    <span class="label">Exchange Terms / Items:</span>
                    <span class="value">{{ $productDetails }}</span>
                </div>
                @endif
                <div class="info-row" style="border-top: 1px dashed #6f42c1; margin-top: 5px; padding-top: 10px;">
                    <span class="label">Total Credited Value:</span>
                    <span class="value"><strong>${{ number_format($amount, 2) }}</strong></span>
                </div>
            </div>
            @elseif(!empty($isProductExchange))
            <div class="info-box" style="border-left-color: #17a2b8; background-color: #f7fcfe;">
                <h3 style="color: #17a2b8;">📦 Product Exchange Barter</h3>
                <div class="info-row">
                    <span class="label">Goods Credited Value:</span>
                    <span class="value">
                        <strong style="color: #17a2b8;">${{ number_format($amount, 2) }}</strong>
                        @if(!empty($productQuantity))
                            <span style="display: inline-block; background-color: #17a2b8; color: #ffffff; font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">
                                {{ number_format($productQuantity) }} units
                                @if(!empty($productUnitPrice))
                                    @ ${{ number_format($productUnitPrice, 2) }}/u
                                @endif
                            </span>
                        @endif
                    </span>
                </div>
                @if(!empty($productDetails))
                <div class="info-row">
                    <span class="label">Exchange Terms / Items:</span>
                    <span class="value">{{ $productDetails }}</span>
                </div>
                @endif
            </div>
            @endif

            <!-- Booking Details -->
            @if($booking)
            <div class="info-box">
                <h3>📋 Booking Details</h3>
                <div class="info-row">
            <span class="label">Booking ID:</span>
                    <span class="value">#{{ $booking->id }}</span>
                </div>
             @if($booth)
                <div class="info-row">
                    <span class="label">Booth Number:</span>
                  <span class="value"><strong>{{ $booth->booth_number }}</strong></span>
                </div>
             @endif
                @if($booking->event)
                <div class="info-row">
                    <span class="label">Event:</span>
             <span class="value">{{ $booking->event->name }}</span>
              </div>
           @endif
             
              @if($booking->balance_amount > 0)
                <div class="divider"></div>
            <div class="info-row">
           <span class="label">Remaining Balance:</span>
             <span class="value"><strong style="color: #dc3545;">${{ number_format($booking->balance_amount, 2) }}</strong></span>
         </div>
                @if($booking->payment_due_date)
            <div class="info-row">
             <span class="label">Balance Due Date:</span>
                <span class="value">{{ \Carbon\Carbon::parse($booking->payment_due_date)->format('F d, Y') }}</span>
                </div>
                @endif
              @endif
            </div>
            @endif
         
          <!-- Client Information -->
            <div class="info-box">
                <h3>👤 Billed To</h3>
         <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $client->name }}</span>
            </div>
                @if($client->company)
                <div class="info-row">
                    <span class="label">Company:</span>
             <span class="value">{{ $client->company }}</span>
          </div>
              @endif
           @if($client->email)
              <div class="info-row">
                    <span class="label">Email:</span>
                 <span class="value">{{ $client->email }}</span>
             </div>
              @endif
           @if($client->phone)
                <div class="info-row">
                    <span class="label">Phone:</span>
              <span class="value">{{ $client->phone }}</span>
           </div>
              @endif
            </div>
            
       @if($payment->notes)
          <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <strong>Notes:</strong><br>
              {{ $payment->notes }}
        </div>
            @endif
            
            <div style="background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; border-radius: 4px; margin: 25px 0;">
            <strong>📄 Keep this receipt for your records.</strong><br>
                This email serves as your official payment receipt. Please save it for your records and future reference.
            </div>
            
            <!-- Action Button -->
       <center>
                <a href="{{ config('app.url') }}/payments/{{ $payment->id }}" class="btn">View Payment Details</a>
        </center>
            
            <p style="margin-top: 30px;">If you have any questions about this payment or need a printed receipt, please don't hesitate to contact us.</p>
            
            <p>Thank you for your business!</p>
          
          <p>Best regards,<br>
            <strong>The KHB Events Team</strong><br>
          {{ config('app.name') }}</p>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>This is an automated receipt. Please keep it for your records.</p>
            <p>Questions? Contact us at support@khbevents.com</p>
         <p>&copy; {{ date('Y') }} KHB Events. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
