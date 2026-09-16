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
