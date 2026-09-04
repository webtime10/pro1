<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Группы и атрибуты (как oc_attribute_group, oc_attribute, oc_*_description в OpenCart 3).
     */
    public function up(): void
    {
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('attribute_group_descriptions', function (Blueprint $table) {
            $table->foreignId('attribute_group_id')->constrained('attribute_groups')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 64);

            $table->primary(['attribute_group_id', 'language_id']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_group_id')->constrained('attribute_groups')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('attribute_descriptions', function (Blueprint $table) {
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 64);

            $table->primary(['attribute_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_descriptions');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_group_descriptions');
        Schema::dropIfExists('attribute_groups');
    }
};
