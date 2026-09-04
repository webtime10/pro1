<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблицы как в OpenCart wt_filter_* (без DB_PREFIX).
     */
    public function up(): void
    {
        Schema::create('wt_filter_option', function (Blueprint $table) {
            $table->increments('option_id');
            $table->string('type', 32)->default('checkbox');
            $table->string('keyword', 255)->default('');
            $table->boolean('status')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('grouping')->default(0);
            $table->boolean('selectbox')->default(false);
            $table->boolean('color')->default(false);
            $table->boolean('image')->default(false);
        });

        Schema::create('wt_filter_option_description', function (Blueprint $table) {
            $table->unsignedInteger('option_id');
            $table->unsignedBigInteger('language_id');
            $table->string('name', 255)->default('');
            $table->text('description')->nullable();
            $table->string('postfix', 32)->default('');

            $table->primary(['option_id', 'language_id']);
        });

        Schema::create('wt_filter_option_to_category', function (Blueprint $table) {
            $table->unsignedInteger('option_id');
            $table->unsignedBigInteger('category_id');

            $table->primary(['option_id', 'category_id']);
        });

        Schema::create('wt_filter_option_to_store', function (Blueprint $table) {
            $table->unsignedInteger('option_id');
            $table->integer('store_id');

            $table->primary(['option_id', 'store_id']);
        });

        Schema::create('wt_filter_option_value', function (Blueprint $table) {
            $table->bigIncrements('value_id');
            $table->unsignedInteger('option_id');
            $table->string('keyword', 255)->default('');
            $table->string('color', 7)->default('');
            $table->string('image', 255)->default('');
            $table->integer('sort_order')->default(0);

            $table->index('option_id');
        });

        Schema::create('wt_filter_option_value_description', function (Blueprint $table) {
            $table->unsignedBigInteger('value_id');
            $table->unsignedInteger('option_id');
            $table->unsignedBigInteger('language_id');
            $table->string('name', 255)->default('');

            $table->primary(['value_id', 'language_id']);
            $table->index('option_id');
        });

        Schema::create('wt_filter_option_value_to_product', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('option_id');
            $table->unsignedBigInteger('value_id');
            $table->decimal('slide_value_min', 15, 4)->default(0);
            $table->decimal('slide_value_max', 15, 4)->default(0);

            $table->primary(['product_id', 'option_id', 'value_id'], 'wt_filter_ov2p_pk');
            $table->index('option_id');
            $table->index('value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wt_filter_option_value_to_product');
        Schema::dropIfExists('wt_filter_option_value_description');
        Schema::dropIfExists('wt_filter_option_value');
        Schema::dropIfExists('wt_filter_option_to_store');
        Schema::dropIfExists('wt_filter_option_to_category');
        Schema::dropIfExists('wt_filter_option_description');
        Schema::dropIfExists('wt_filter_option');
    }
};
