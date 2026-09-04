<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('option_values', 'color')) {
            Schema::table('option_values', function (Blueprint $table) {
                $table->string('color', 7)->default('')->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('option_values', 'color')) {
            Schema::table('option_values', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
