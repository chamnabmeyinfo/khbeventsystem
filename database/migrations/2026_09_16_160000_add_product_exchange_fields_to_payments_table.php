<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cash_amount')) {
                $table->decimal('cash_amount', 10, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('payments', 'product_amount')) {
                $table->decimal('product_amount', 10, 2)->nullable()->after('cash_amount');
            }
            if (! Schema::hasColumn('payments', 'product_details')) {
                $table->text('product_details')->nullable()->after('product_amount');
            }
            if (! Schema::hasColumn('payments', 'cash_payment_method')) {
                $table->string('cash_payment_method', 50)->nullable()->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('payments', 'cash_amount')) {
                $columnsToDrop[] = 'cash_amount';
            }
            if (Schema::hasColumn('payments', 'product_amount')) {
                $columnsToDrop[] = 'product_amount';
            }
            if (Schema::hasColumn('payments', 'product_details')) {
                $columnsToDrop[] = 'product_details';
            }
            if (Schema::hasColumn('payments', 'cash_payment_method')) {
                $columnsToDrop[] = 'cash_payment_method';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
