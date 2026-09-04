<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Составной индекс под курсорную пагинацию админ-списка: ORDER BY sort_order, id DESC.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['sort_order', 'id'], 'products_sort_order_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_sort_order_id_index');
        });
    }
};
