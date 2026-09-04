<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Support\CatalogUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Логика корзины как в OpenCart (catalog/model/checkout/cart):
 * ключ строки = customer_id/session_id + product_id + option + subscription_plan_id.
 */
class CartService
{
    public function sessionId(): string
    {
        $id = (string) session()->getId();

        return substr($id, 0, 32);
    }

    public function customerId(): int
    {
        return (int) session('customer_id', 0);
    }

    /** Нормализация опций как в OC (ключ = product_option_id). */
    public function normalizeOption(array $option): array
    {
        $normalized = [];

        foreach ($option as $productOptionId => $value) {
            $productOptionId = (int) $productOptionId;
            if ($productOptionId <= 0) {
                continue;
            }

            if (is_array($value)) {
                $vals = array_values(array_filter(array_map('intval', $value)));
                sort($vals);
                if ($vals !== []) {
                    $normalized[$productOptionId] = $vals;
                }
            } else {
                $value = is_string($value) ? trim($value) : $value;
                if ($value === '' || $value === null) {
                    continue;
                }
                if (is_numeric($value)) {
                    $normalized[$productOptionId] = (int) $value;
                } else {
                    $normalized[$productOptionId] = (string) $value;
                }
            }
        }

        ksort($normalized);

        return $normalized;
    }

    public function encodeOption(array $option): string
    {
        return json_encode($this->normalizeOption($option), JSON_UNESCAPED_UNICODE);
    }

    public function decodeOption(?string $option): array
    {
        if (! $option) {
            return [];
        }

        $decoded = json_decode($option, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function query()
    {
        $customerId = $this->customerId();
        $sessionId = $this->sessionId();

        return Cart::query()->where(function ($q) use ($customerId, $sessionId) {
            if ($customerId > 0) {
                $q->where('customer_id', $customerId);
            } else {
                $q->where('customer_id', 0)->where('session_id', $sessionId);
            }
        });
    }

    public function add(int $productId, int $quantity = 1, array $option = [], int $subscriptionPlanId = 0): Cart
    {
        $product = Product::query()
            ->where('id', $productId)
            ->where('status', true)
            ->with([
                'productOptions.option.descriptions',
                'productOptions.values.optionValue.descriptions',
            ])
            ->firstOrFail();

        $quantity = max(1, $quantity);
        $minimum = max(1, (int) $product->minimum);
        if ($quantity < $minimum) {
            $quantity = $minimum;
        }

        $option = $this->normalizeOption($option);
        $this->validateRequiredOptions($product, $option);

        [$unitPrice] = $this->calculatePrice($product, $option);
        $optionJson = $this->encodeOption($option);

        $existing = $this->query()
            ->where('product_id', $productId)
            ->where('subscription_plan_id', $subscriptionPlanId)
            ->where('option', $optionJson)
            ->first();

        if ($existing) {
            $existing->quantity = (int) $existing->quantity + $quantity;
            if (! $existing->override) {
                $existing->price = $unitPrice;
            }
            $existing->save();

            return $existing;
        }

        return Cart::query()->create([
            'api_id' => 0,
            'customer_id' => $this->customerId(),
            'session_id' => $this->sessionId(),
            'product_id' => $productId,
            'subscription_plan_id' => $subscriptionPlanId,
            'option' => $optionJson,
            'quantity' => $quantity,
            'override' => false,
            'price' => $unitPrice,
            'date_added' => now(),
        ]);
    }

    public function update(int $cartId, int $quantity): void
    {
        $row = $this->query()->where('cart_id', $cartId)->firstOrFail();

        if ($quantity <= 0) {
            $row->delete();

            return;
        }

        $product = Product::query()->find($row->product_id);
        $minimum = max(1, (int) ($product?->minimum ?? 1));
        $row->quantity = max($minimum, $quantity);
        $row->save();
    }

    public function remove(int $cartId): void
    {
        $this->query()->where('cart_id', $cartId)->delete();
    }

    public function clear(): void
    {
        $this->query()->delete();
    }

    public function countProducts(): int
    {
        return (int) $this->query()->sum('quantity');
    }

    /**
     * Позиции корзины с рассчитанными ценами (как products в OC cart).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function products(?Language $lang = null): Collection
    {
        $lang = $lang ?? Language::getDefault();
        $rows = $this->query()->orderBy('cart_id')->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $productIds = $rows->pluck('product_id')->unique()->all();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', true)
            ->with([
                'descriptions' => fn ($q) => $lang ? $q->where('language_id', $lang->id) : $q,
                'categories.descriptions' => fn ($q) => $lang ? $q->where('language_id', $lang->id) : $q,
                'productOptions.option.descriptions' => fn ($q) => $lang ? $q->where('language_id', $lang->id) : $q,
                'productOptions.values.optionValue.descriptions' => fn ($q) => $lang ? $q->where('language_id', $lang->id) : $q,
            ])
            ->get()
            ->keyBy('id');

        return $rows->map(function (Cart $row) use ($products, $lang) {
            $product = $products->get($row->product_id);
            if (! $product) {
                return null;
            }

            $option = $this->decodeOption($row->option);
            [$unitPrice, $optionDetails] = $this->calculatePrice($product, $option, $lang);

            if ($row->override) {
                $unitPrice = (float) $row->price;
            }

            $pd = $lang
                ? $product->descriptions->firstWhere('language_id', $lang->id)
                : $product->descriptions->first();

            $img = $product->image
                ? (str_starts_with($product->image, 'http')
                    ? $product->image
                    : asset('storage/'.ltrim($product->image, '/')))
                : null;

            $qty = (int) $row->quantity;

            return [
                'cart_id' => (int) $row->cart_id,
                'product_id' => (int) $product->id,
                'name' => $pd->name ?? $product->model,
                'model' => $product->model,
                'slug' => $pd->slug ?? null,
                'url' => CatalogUrl::product($product, $lang),
                'image' => $img,
                'option' => $optionDetails,
                'quantity' => $qty,
                'price' => $unitPrice,
                'total' => $unitPrice * $qty,
                'stock' => ! $product->subtract || $product->quantity >= $qty,
                'minimum' => max(1, (int) $product->minimum),
                'shipping' => (bool) $product->shipping,
            ];
        })->filter()->values();
    }

    /**
     * Totals как в OC: sub_total + total (shipping/tax пока 0).
     *
     * @return list<array{code: string, title: string, value: float, sort_order: int}>
     */
    public function totals(?Language $lang = null): array
    {
        $products = $this->products($lang);
        $subTotal = (float) $products->sum('total');

        return [
            [
                'code' => 'sub_total',
                'title' => 'Сумма',
                'value' => $subTotal,
                'sort_order' => 1,
            ],
            [
                'code' => 'total',
                'title' => 'Итого',
                'value' => $subTotal,
                'sort_order' => 9,
            ],
        ];
    }

    public function summary(?Language $lang = null): array
    {
        $products = $this->products($lang);
        $totals = $this->totals($lang);
        $totalRow = collect($totals)->firstWhere('code', 'total');

        return [
            'count' => (int) $products->sum('quantity'),
            'products' => $products,
            'totals' => $totals,
            'total' => (float) ($totalRow['value'] ?? 0),
        ];
    }

    private function validateRequiredOptions(Product $product, array $option): void
    {
        $errors = [];

        foreach ($product->productOptions as $po) {
            if (! $po->required) {
                continue;
            }
            if (! array_key_exists($po->id, $option) || $option[$po->id] === '' || $option[$po->id] === []) {
                $name = $po->option?->descriptions->first()?->name ?? ('Опция #'.$po->option_id);
                $errors['option.'.$po->id] = 'Выберите «'.$name.'»';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array{0: float, 1: list<array{product_option_id: int, product_option_value_id: int, name: string, value: string, type: string}>}
     */
    private function calculatePrice(Product $product, array $option, ?Language $lang = null): array
    {
        $price = (float) $product->price;
        $details = [];

        foreach ($option as $productOptionId => $value) {
            /** @var ProductOption|null $po */
            $po = $product->productOptions->firstWhere('id', (int) $productOptionId);
            if (! $po) {
                continue;
            }

            $optName = $lang
                ? ($po->option?->descriptions->firstWhere('language_id', $lang->id)?->name
                    ?? $po->option?->descriptions->first()?->name
                    ?? 'Опция')
                : ($po->option?->descriptions->first()?->name ?? 'Опция');
            $type = $po->option?->type ?? 'select';

            $values = is_array($value) ? $value : [$value];

            foreach ($values as $raw) {
                if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                    /** @var ProductOptionValue|null $pov */
                    $pov = $po->values->firstWhere('id', (int) $raw);
                    if (! $pov) {
                        continue;
                    }
                    $valName = $lang
                        ? ($pov->optionValue?->descriptions->firstWhere('language_id', $lang->id)?->name
                            ?? $pov->optionValue?->descriptions->first()?->name
                            ?? (string) $pov->id)
                        : ($pov->optionValue?->descriptions->first()?->name ?? (string) $pov->id);

                    $optPrice = (float) $pov->price;
                    if ($pov->price_prefix === '-') {
                        $price -= $optPrice;
                    } else {
                        $price += $optPrice;
                    }

                    $details[] = [
                        'product_option_id' => (int) $po->id,
                        'product_option_value_id' => (int) $pov->id,
                        'name' => $optName,
                        'value' => $valName,
                        'type' => $type,
                    ];
                } else {
                    $details[] = [
                        'product_option_id' => (int) $po->id,
                        'product_option_value_id' => 0,
                        'name' => $optName,
                        'value' => (string) $raw,
                        'type' => $type,
                    ];
                }
            }
        }

        return [max(0, $price), $details];
    }

    /** Привязать гостевую корзину к customer_id после логина (как OC). */
    public function mergeGuestToCustomer(int $customerId): void
    {
        if ($customerId <= 0) {
            return;
        }

        $sessionId = $this->sessionId();

        DB::table('cart')
            ->where('customer_id', 0)
            ->where('session_id', $sessionId)
            ->update(['customer_id' => $customerId]);
    }
}
