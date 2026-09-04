<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Опции и значения (как oc_option, oc_option_value, oc_*_description в OpenCart 3).
     */
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('option_descriptions', function (Blueprint $table) {
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 128);

            $table->primary(['option_id', 'language_id']);
        });

        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->string('image', 255)->default('');
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('option_value_descriptions', function (Blueprint $table) {
            $table->foreignId('option_value_id')->constrained('option_values')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->string('name', 128);

            $table->primary(['option_value_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_value_descriptions');
        Schema::dropIfExists('option_values');
        Schema::dropIfExists('option_descriptions');
        Schema::dropIfExists('options');
    }
};
