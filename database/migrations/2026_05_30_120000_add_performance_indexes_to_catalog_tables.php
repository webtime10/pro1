<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Индексы для админки/витрины на 1M+ товаров (ТЗ: оптимизация выборок).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('sku', 'products_sku_index');
            $table->index('model', 'products_model_index');
            $table->index('quantity', 'products_quantity_index');
            $table->index(['status', 'id'], 'products_status_id_index');
        });

        Schema::table('category_product', function (Blueprint $table) {
            $table->index(['category_id', 'product_id'], 'category_product_category_product_index');
        });

        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->index(['language_id', 'name'], 'product_descriptions_language_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_sku_index');
            $table->dropIndex('products_model_index');
            $table->dropIndex('products_quantity_index');
            $table->dropIndex('products_status_id_index');
        });

        Schema::table('category_product', function (Blueprint $table) {
            $table->dropIndex('category_product_category_product_index');
        });

        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->dropIndex('product_descriptions_language_name_index');
        });
    }
};
