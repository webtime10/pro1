<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderOption;
use App\Models\OrderProduct;
use App\Models\OrderTotal;
use App\Models\Product;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Создание заказа в таблицы OpenCart: order, order_product, order_option, order_total, order_history.
 */
class CheckoutService
{
    public function __construct(private CartService $cart) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function placeOrder(array $data, Request $request, ?Language $lang = null): Order
    {
        $lang = $lang ?? Language::getDefault();
        $summary = $this->cart->summary($lang);

        if ($summary['products']->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Корзина пуста.',
            ]);
        }

        foreach ($summary['products'] as $item) {
            if (! $item['stock']) {
                throw ValidationException::withMessages([
                    'cart' => 'Товар «'.$item['name'].'» недоступен в нужном количестве.',
                ]);
            }
        }

        $country = Country::query()->find((int) ($data['country_id'] ?? 0));
        $zone = Zone::query()->find((int) ($data['zone_id'] ?? 0));

        $paymentMethod = json_encode([
            'name' => 'Оплата при получении',
            'code' => 'cod',
        ], JSON_UNESCAPED_UNICODE);

        $shippingMethod = json_encode([
            'name' => 'Доставка',
            'code' => 'flat.flat',
            'cost' => 0,
        ], JSON_UNESCAPED_UNICODE);

        return DB::transaction(function () use ($data, $request, $lang, $summary, $country, $zone, $paymentMethod, $shippingMethod) {
            $now = now();
            $total = (float) $summary['total'];

            $order = Order::query()->create([
                'subscription_id' => 0,
                'invoice_no' => 0,
                'invoice_prefix' => 'INV-'.date('Y').'-',
                'transaction_id' => '',
                'store_id' => 0,
                'store_name' => (string) config('app.name'),
                'store_url' => rtrim((string) config('app.url'), '/').'/',
                'customer_id' => $this->cart->customerId(),
                'customer_group_id' => 1,
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'custom_field' => '',
                'payment_address_id' => 0,
                'payment_firstname' => $data['firstname'],
                'payment_lastname' => $data['lastname'],
                'payment_company' => $data['company'] ?? '',
                'payment_address_1' => $data['address_1'],
                'payment_address_2' => $data['address_2'] ?? '',
                'payment_city' => $data['city'],
                'payment_postcode' => $data['postcode'] ?? '',
                'payment_country' => $country?->name ?? '',
                'payment_country_id' => (int) ($country?->country_id ?? 0),
                'payment_zone' => $zone?->name ?? '',
                'payment_zone_id' => (int) ($zone?->zone_id ?? 0),
                'payment_address_format' => '',
                'payment_custom_field' => '',
                'payment_method' => $paymentMethod,
                'shipping_address_id' => 0,
                'shipping_firstname' => $data['firstname'],
                'shipping_lastname' => $data['lastname'],
                'shipping_company' => $data['company'] ?? '',
                'shipping_address_1' => $data['address_1'],
                'shipping_address_2' => $data['address_2'] ?? '',
                'shipping_city' => $data['city'],
                'shipping_postcode' => $data['postcode'] ?? '',
                'shipping_country' => $country?->name ?? '',
                'shipping_country_id' => (int) ($country?->country_id ?? 0),
                'shipping_zone' => $zone?->name ?? '',
                'shipping_zone_id' => (int) ($zone?->zone_id ?? 0),
                'shipping_address_format' => '',
                'shipping_custom_field' => '',
                'shipping_method' => $shippingMethod,
                'comment' => $data['comment'] ?? '',
                'total' => $total,
                'order_status_id' => 1,
                'affiliate_id' => 0,
                'commission' => 0,
                'marketing_id' => 0,
                'tracking' => '',
                'language_id' => (int) ($lang?->id ?? 0),
                'language_code' => (string) ($lang?->code ?? 'ru'),
                'currency_id' => 0,
                'currency_code' => 'RUB',
                'currency_value' => 1,
                'ip' => (string) $request->ip(),
                'forwarded_ip' => (string) $request->header('X-Forwarded-For', ''),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'accept_language' => substr((string) $request->header('Accept-Language', ''), 0, 255),
                'date_added' => $now,
                'date_modified' => $now,
            ]);

            foreach ($summary['products'] as $item) {
                $orderProduct = OrderProduct::query()->create([
                    'order_id' => $order->order_id,
                    'product_id' => $item['product_id'],
                    'master_id' => 0,
                    'name' => $item['name'],
                    'model' => $item['model'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                    'tax' => 0,
                    'reward' => 0,
                ]);

                foreach ($item['option'] as $opt) {
                    OrderOption::query()->create([
                        'order_id' => $order->order_id,
                        'order_product_id' => $orderProduct->order_product_id,
                        'product_option_id' => $opt['product_option_id'],
                        'product_option_value_id' => $opt['product_option_value_id'],
                        'name' => $opt['name'],
                        'value' => $opt['value'],
                        'type' => $opt['type'],
                    ]);
                }

                $product = Product::query()->find($item['product_id']);
                if ($product && $product->subtract) {
                    $product->quantity = max(0, (int) $product->quantity - (int) $item['quantity']);
                    $product->save();
                }
            }

            foreach ($summary['totals'] as $totalRow) {
                OrderTotal::query()->create([
                    'order_id' => $order->order_id,
                    'extension' => 'opencart',
                    'code' => $totalRow['code'],
                    'title' => $totalRow['title'],
                    'value' => $totalRow['value'],
                    'sort_order' => $totalRow['sort_order'],
                ]);
            }

            OrderHistory::query()->create([
                'order_id' => $order->order_id,
                'order_status_id' => 1,
                'notify' => false,
                'comment' => 'Заказ создан',
                'date_added' => $now,
            ]);

            $this->cart->clear();

            return $order;
        });
    }
}
