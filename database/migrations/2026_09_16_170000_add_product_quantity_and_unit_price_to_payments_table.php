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
            if (! Schema::hasColumn('payments', 'product_quantity')) {
                $table->decimal('product_quantity', 10, 2)->nullable()->after('product_amount');
            }
            if (! Schema::hasColumn('payments', 'product_unit_price')) {
                $table->decimal('product_unit_price', 10, 2)->nullable()->after('product_quantity');
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
            if (Schema::hasColumn('payments', 'product_quantity')) {
                $columnsToDrop[] = 'product_quantity';
            }
            if (Schema::hasColumn('payments', 'product_unit_price')) {
                $columnsToDrop[] = 'product_unit_price';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
