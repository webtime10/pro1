<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Стандартные фильтры OpenCart 3 (filter_group / filter / product_filter).
     * Нужны для шага copy_filter в WT Filter.
     */
    public function up(): void
    {
        if (! Schema::hasTable('filter_groups')) {
            Schema::create('filter_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasTable('filter_group_descriptions')) {
            Schema::create('filter_group_descriptions', function (Blueprint $table) {
                $table->foreignId('filter_group_id')->constrained('filter_groups')->cascadeOnDelete();
                $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
                $table->string('name', 128)->default('');

                $table->primary(['filter_group_id', 'language_id']);
            });
        }

        if (! Schema::hasTable('filters')) {
            Schema::create('filters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('filter_group_id')->constrained('filter_groups')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasTable('filter_descriptions')) {
            Schema::create('filter_descriptions', function (Blueprint $table) {
                $table->foreignId('filter_id')->constrained('filters')->cascadeOnDelete();
                $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
                $table->foreignId('filter_group_id')->constrained('filter_groups')->cascadeOnDelete();
                $table->string('name', 128)->default('');

                $table->primary(['filter_id', 'language_id']);
            });
        }

        if (! Schema::hasTable('product_filters')) {
            Schema::create('product_filters', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('filter_id')->constrained('filters')->cascadeOnDelete();

                $table->primary(['product_id', 'filter_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_filters');
        Schema::dropIfExists('filter_descriptions');
        Schema::dropIfExists('filters');
        Schema::dropIfExists('filter_group_descriptions');
        Schema::dropIfExists('filter_groups');
    }
};
