<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wt_filter_option', function (Blueprint $table) {
            if (! Schema::hasColumn('wt_filter_option', 'expanded_desktop')) {
                $table->boolean('expanded_desktop')->default(true)->after('image');
            }
            if (! Schema::hasColumn('wt_filter_option', 'expanded_mobile')) {
                $table->boolean('expanded_mobile')->default(false)->after('expanded_desktop');
            }
        });

        Schema::table('wt_filter_option_value', function (Blueprint $table) {
            if (! Schema::hasColumn('wt_filter_option_value', 'value_numeric')) {
                $table->decimal('value_numeric', 15, 4)->nullable()->after('image');
                $table->index(['option_id', 'value_numeric'], 'wt_filter_value_numeric');
            }
        });

        if (! Schema::hasColumn('wt_filter_option_value_to_product', 'category_id')) {
            Schema::table('wt_filter_option_value_to_product', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->default(0)->after('value_id');
            });

            // Expand product→filter rows into category-scoped rows (как OC ensureSchema).
            DB::statement("
                INSERT IGNORE INTO wt_filter_option_value_to_product
                    (product_id, option_id, value_id, category_id, slide_value_min, slide_value_max)
                SELECT w.product_id, w.option_id, w.value_id, cp.category_id, w.slide_value_min, w.slide_value_max
                FROM wt_filter_option_value_to_product w
                INNER JOIN category_product cp ON cp.product_id = w.product_id
                WHERE w.category_id = 0 AND cp.category_id > 0
            ");

            DB::table('wt_filter_option_value_to_product')->where('category_id', 0)->delete();

            try {
                DB::statement('ALTER TABLE wt_filter_option_value_to_product DROP PRIMARY KEY');
            } catch (\Throwable) {
                // уже другой PK
            }

            DB::statement('ALTER TABLE wt_filter_option_value_to_product
                ADD PRIMARY KEY (product_id, option_id, value_id, category_id)');

            Schema::table('wt_filter_option_value_to_product', function (Blueprint $table) {
                $table->index(['category_id', 'option_id', 'value_id'], 'wt_filter_cat_opt_val');
            });
        }

        if (! Schema::hasTable('wt_filter_settings')) {
            Schema::create('wt_filter_settings', function (Blueprint $table) {
                $table->string('key', 64)->primary();
                $table->text('value')->nullable();
            });

            DB::table('wt_filter_settings')->insert([
                'key' => 'group_expanded',
                'value' => json_encode([
                    'p' => ['desktop' => 1, 'mobile' => 0],
                    'm' => ['desktop' => 1, 'mobile' => 0],
                    's' => ['desktop' => 1, 'mobile' => 0],
                    'd' => ['desktop' => 1, 'mobile' => 0],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            DB::table('wt_filter_settings')->insert([
                'key' => 'show_counts',
                'value' => '1',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wt_filter_settings');

        // Не откатываем category_id / PK — данные уже развернуты.
        Schema::table('wt_filter_option', function (Blueprint $table) {
            if (Schema::hasColumn('wt_filter_option', 'expanded_mobile')) {
                $table->dropColumn('expanded_mobile');
            }
            if (Schema::hasColumn('wt_filter_option', 'expanded_desktop')) {
                $table->dropColumn('expanded_desktop');
            }
        });
    }
};
