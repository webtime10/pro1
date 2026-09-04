<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Таблицы корзины / покупателя / заказа по схеме OpenCart 4
 * (поля как в upload/system/helper/db_schema.php, без префикса oc_).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country', function (Blueprint $table) {
            $table->increments('country_id');
            $table->string('name', 128);
            $table->string('iso_code_2', 2)->default('');
            $table->string('iso_code_3', 3)->default('');
            $table->unsignedInteger('address_format_id')->default(0);
            $table->boolean('postcode_required')->default(false);
            $table->boolean('status')->default(true);
        });

        Schema::create('zone', function (Blueprint $table) {
            $table->increments('zone_id');
            $table->unsignedInteger('country_id')->default(0);
            $table->string('name', 128);
            $table->string('code', 32)->default('');
            $table->boolean('status')->default(true);
            $table->index('country_id');
        });

        Schema::create('customer', function (Blueprint $table) {
            $table->increments('customer_id');
            $table->unsignedInteger('customer_group_id')->default(1);
            $table->unsignedInteger('store_id')->default(0);
            $table->unsignedInteger('language_id')->default(0);
            $table->string('firstname', 32)->default('');
            $table->string('lastname', 32)->default('');
            $table->string('email', 96)->default('');
            $table->string('telephone', 32)->default('');
            $table->string('password', 255)->default('');
            $table->text('custom_field')->nullable();
            $table->boolean('newsletter')->default(false);
            $table->string('ip', 40)->default('');
            $table->boolean('status')->default(true);
            $table->boolean('safe')->default(false);
            $table->text('token')->nullable();
            $table->string('code', 40)->default('');
            $table->dateTime('date_added')->nullable();
            $table->index('email');
        });

        Schema::create('address', function (Blueprint $table) {
            $table->increments('address_id');
            $table->unsignedInteger('customer_id')->default(0);
            $table->string('firstname', 32)->default('');
            $table->string('lastname', 32)->default('');
            $table->string('company', 60)->default('');
            $table->string('address_1', 128)->default('');
            $table->string('address_2', 128)->default('');
            $table->string('city', 128)->default('');
            $table->string('postcode', 10)->default('');
            $table->unsignedInteger('country_id')->default(0);
            $table->unsignedInteger('zone_id')->default(0);
            $table->text('custom_field')->nullable();
            $table->boolean('default')->default(false);
            $table->index('customer_id');
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->increments('cart_id');
            $table->unsignedInteger('api_id')->default(0);
            $table->unsignedInteger('customer_id')->default(0);
            $table->string('session_id', 32);
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('subscription_plan_id')->default(0);
            $table->text('option')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('override')->default(false);
            $table->decimal('price', 15, 4)->default(0);
            $table->dateTime('date_added')->nullable();
            $table->index(['customer_id', 'session_id', 'product_id', 'subscription_plan_id'], 'cart_lookup');
        });

        Schema::create('order_status', function (Blueprint $table) {
            $table->unsignedInteger('order_status_id');
            $table->unsignedInteger('language_id');
            $table->string('name', 32);
            $table->primary(['order_status_id', 'language_id']);
        });

        Schema::create('order', function (Blueprint $table) {
            $table->increments('order_id');
            $table->unsignedInteger('subscription_id')->default(0);
            $table->unsignedInteger('invoice_no')->default(0);
            $table->string('invoice_prefix', 26)->default('');
            $table->string('transaction_id', 100)->default('');
            $table->unsignedInteger('store_id')->default(0);
            $table->string('store_name', 64)->default('');
            $table->string('store_url', 255)->default('');
            $table->unsignedInteger('customer_id')->default(0);
            $table->unsignedInteger('customer_group_id')->default(0);
            $table->string('firstname', 32)->default('');
            $table->string('lastname', 32)->default('');
            $table->string('email', 96)->default('');
            $table->string('telephone', 32)->default('');
            $table->text('custom_field')->nullable();
            $table->unsignedInteger('payment_address_id')->default(0);
            $table->string('payment_firstname', 32)->default('');
            $table->string('payment_lastname', 32)->default('');
            $table->string('payment_company', 60)->default('');
            $table->string('payment_address_1', 128)->default('');
            $table->string('payment_address_2', 128)->default('');
            $table->string('payment_city', 128)->default('');
            $table->string('payment_postcode', 10)->default('');
            $table->string('payment_country', 128)->default('');
            $table->unsignedInteger('payment_country_id')->default(0);
            $table->string('payment_zone', 128)->default('');
            $table->unsignedInteger('payment_zone_id')->default(0);
            $table->text('payment_address_format')->nullable();
            $table->text('payment_custom_field')->nullable();
            $table->text('payment_method')->nullable();
            $table->unsignedInteger('shipping_address_id')->default(0);
            $table->string('shipping_firstname', 32)->default('');
            $table->string('shipping_lastname', 32)->default('');
            $table->string('shipping_company', 60)->default('');
            $table->string('shipping_address_1', 128)->default('');
            $table->string('shipping_address_2', 128)->default('');
            $table->string('shipping_city', 128)->default('');
            $table->string('shipping_postcode', 10)->default('');
            $table->string('shipping_country', 128)->default('');
            $table->unsignedInteger('shipping_country_id')->default(0);
            $table->string('shipping_zone', 128)->default('');
            $table->unsignedInteger('shipping_zone_id')->default(0);
            $table->text('shipping_address_format')->nullable();
            $table->text('shipping_custom_field')->nullable();
            $table->text('shipping_method')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('total', 15, 4)->default(0);
            $table->unsignedInteger('order_status_id')->default(0);
            $table->unsignedInteger('affiliate_id')->default(0);
            $table->decimal('commission', 15, 4)->default(0);
            $table->unsignedInteger('marketing_id')->default(0);
            $table->string('tracking', 64)->default('');
            $table->unsignedInteger('language_id')->default(0);
            $table->string('language_code', 5)->default('');
            $table->unsignedInteger('currency_id')->default(0);
            $table->string('currency_code', 3)->default('RUB');
            $table->decimal('currency_value', 15, 8)->default(1);
            $table->string('ip', 40)->default('');
            $table->string('forwarded_ip', 40)->default('');
            $table->string('user_agent', 255)->default('');
            $table->string('accept_language', 255)->default('');
            $table->dateTime('date_added')->nullable();
            $table->dateTime('date_modified')->nullable();
            $table->index('customer_id');
            $table->index('order_status_id');
        });

        Schema::create('order_product', function (Blueprint $table) {
            $table->increments('order_product_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id')->default(0);
            $table->unsignedInteger('master_id')->default(0);
            $table->string('name', 255)->default('');
            $table->string('model', 64)->default('');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('tax', 15, 4)->default(0);
            $table->integer('reward')->default(0);
            $table->index('order_id');
        });

        Schema::create('order_option', function (Blueprint $table) {
            $table->increments('order_option_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('order_product_id');
            $table->unsignedInteger('product_option_id')->default(0);
            $table->unsignedInteger('product_option_value_id')->default(0);
            $table->string('name', 255)->default('');
            $table->text('value')->nullable();
            $table->string('type', 32)->default('');
            $table->index('order_id');
            $table->index('order_product_id');
        });

        Schema::create('order_total', function (Blueprint $table) {
            $table->increments('order_total_id');
            $table->unsignedInteger('order_id');
            $table->string('extension', 255)->default('');
            $table->string('code', 32)->default('');
            $table->string('title', 255)->default('');
            $table->decimal('value', 15, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->index('order_id');
        });

        Schema::create('order_history', function (Blueprint $table) {
            $table->increments('order_history_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('order_status_id')->default(0);
            $table->boolean('notify')->default(false);
            $table->text('comment')->nullable();
            $table->dateTime('date_added')->nullable();
            $table->index('order_id');
        });

        // Базовые справочники (как после установки OC)
        DB::table('country')->insert([
            'country_id' => 176,
            'name' => 'Russian Federation',
            'iso_code_2' => 'RU',
            'iso_code_3' => 'RUS',
            'address_format_id' => 0,
            'postcode_required' => true,
            'status' => true,
        ]);

        $zones = [
            ['zone_id' => 2761, 'country_id' => 176, 'name' => 'Москва', 'code' => 'MOW', 'status' => true],
            ['zone_id' => 2762, 'country_id' => 176, 'name' => 'Санкт-Петербург', 'code' => 'SPE', 'status' => true],
            ['zone_id' => 2763, 'country_id' => 176, 'name' => 'Московская область', 'code' => 'MOS', 'status' => true],
        ];
        DB::table('zone')->insert($zones);

        $statuses = [
            [1, 1, 'Ожидание'],
            [2, 1, 'В обработке'],
            [3, 1, 'Доставлен'],
            [5, 1, 'Завершён'],
            [7, 1, 'Отменён'],
        ];
        foreach ($statuses as [$id, $lang, $name]) {
            DB::table('order_status')->insert([
                'order_status_id' => $id,
                'language_id' => $lang,
                'name' => $name,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_history');
        Schema::dropIfExists('order_total');
        Schema::dropIfExists('order_option');
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('order');
        Schema::dropIfExists('order_status');
        Schema::dropIfExists('cart');
        Schema::dropIfExists('address');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('zone');
        Schema::dropIfExists('country');
    }
};
