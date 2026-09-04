<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wt_filter_option_value_to_product')) {
            return;
        }

        try {
            Schema::table('wt_filter_option_value_to_product', function (Blueprint $table) {
                $table->index(['category_id', 'product_id'], 'wt_filter_ov2p_cat_product');
            });
        } catch (\Throwable) {
            // индекс уже есть
        }
    }

    public function down(): void
    {
        try {
            Schema::table('wt_filter_option_value_to_product', function (Blueprint $table) {
                $table->dropIndex('wt_filter_ov2p_cat_product');
            });
        } catch (\Throwable) {
        }
    }
};
