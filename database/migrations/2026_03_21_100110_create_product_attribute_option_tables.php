<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Связи товар ↔ атрибуты и опции (как oc_product_attribute, oc_product_option, oc_product_option_value).
     */
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->text('text');

            $table->primary(['product_id', 'attribute_id', 'language_id']);
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->text('value');
            $table->boolean('required')->default(false);
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained('option_values')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('subtract')->default(false);
            $table->decimal('price', 15, 4)->default(0);
            $table->string('price_prefix', 1)->default('+');
            $table->integer('points')->default(0);
            $table->string('points_prefix', 1)->default('+');
            $table->decimal('weight', 15, 8)->default(0);
            $table->string('weight_prefix', 1)->default('+');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_attributes');
    }
};
