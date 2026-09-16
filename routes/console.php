<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('khb:check-booking {id=829}', function ($id) {
    $b = \App\Models\Book::find($id);
    if (!$b) {
        $this->error("Booking not found");
        return;
    }
    $this->info("Booking #{$b->id}: Total={$b->total_amount}, Paid={$b->paid_amount}, Balance={$b->balance_amount}, Status={$b->status}");
    foreach ($b->payments as $p) {
        $this->line("Payment #{$p->id}: Amount={$p->amount}, Cash={$p->cash_amount}, ProdAmt={$p->product_amount}, Qty={$p->product_quantity}, UnitPrice={$p->product_unit_price}, Method={$p->payment_method}, Status={$p->status}, Notes={$p->notes}");
    }
});

Artisan::command('khb:fix-payment-37', function () {
    $p = \App\Models\Payment::find(37);
    if (!$p) {
        $this->error("Payment 37 not found");
        return;
    }

    $p->update([
        'amount' => 680.00,
        'cash_amount' => 650.00,
        'product_amount' => 30.00,
        'product_quantity' => 1,
        'product_unit_price' => 30.00,
        'payment_method' => \App\Models\Payment::METHOD_SPLIT,
        'cash_payment_method' => 'bank_transfer',
        'product_details' => '30 Exchange with Product. ៣០ ដុល្លារ ផ្លាស់ប្តូជាមួយផលិតផល។',
        'notes' => "30 Exchange with Product. ៣០ ដុល្លារ ផ្លាស់ប្តូជាមួយផលិតផល。\n[Product Exchange: 1 units @ $30.00 = $30.00 - 30 Exchange with Product. ៣០ ដុល្លារ ផ្លាស់ប្តូជាមួយផលិតផល。]",
    ]);

    $b = \App\Models\Book::find($p->booking_id);
    if ($b) {
        $b->updatePaymentAmounts();

        // Update booth statuses if fully paid
        $boothIds = json_decode($b->boothid, true) ?? [];
        if (!empty($boothIds)) {
            $booths = \App\Models\Booth::whereIn('id', $boothIds)->get();
            foreach ($booths as $booth) {
                if ($b->balance_amount <= 0) {
                    $booth->deposit_paid = (float)$booth->price;
                    $booth->balance_paid = 0;
                    $booth->status = \App\Models\Booth::STATUS_PAID;
                    $booth->save();
                }
            }
        }
        $this->info("Payment #37 fixed and Booking #{$b->id} re-synced: Total={$b->total_amount}, Paid={$b->paid_amount}, Balance={$b->balance_amount}, Status={$b->status}");
    }
});

Artisan::command('khb:sync-all-bookings', function () {
    $bookings = \App\Models\Book::all();
    $count = 0;
    foreach ($bookings as $b) {
        $b->updatePaymentAmounts();
        $count++;
    }
    $this->info("Successfully re-calculated payment amounts and balances for {$count} bookings.");
});

Artisan::command('khb:test-table-row {id=829}', function ($id) {
    $b = \App\Models\Book::with('user', 'client', 'floorPlan')->find($id);
    if (!$b) {
        $this->error("Booking not found");
        return;
    }
    $admin = \App\Models\User::where('type', 1)->first() ?? \App\Models\User::first();
    auth()->login($admin);
    $rendered = view('books.partials.table-row', ['book' => $b, 'rowNumber' => 1])->render();
    $this->info("Successfully rendered table-row for booking #{$b->id}! Length: " . strlen($rendered));
    $this->line("Sample snippet:");
    if (preg_match('/<td class="books-col-team.*?<\/td>/s', $rendered, $matches)) {
        $this->line(trim($matches[0]));
    }
});

