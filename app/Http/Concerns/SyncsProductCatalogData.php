<?php

namespace App\Http\Concerns;

use App\Models\Option;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Http\Request;

trait SyncsProductCatalogData
{
    protected function syncProductAttributes(Product $product, Request $request): void
    {
        ProductAttribute::query()->where('product_id', $product->id)->delete();

        $rows = $request->input('product_attribute', []);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['attribute_id'])) {
                continue;
            }
            $texts = $row['text'] ?? [];
            if (! is_array($texts)) {
                continue;
            }
            foreach ($texts as $languageId => $text) {
                if ($text === null || $text === '') {
                    continue;
                }
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'attribute_id' => (int) $row['attribute_id'],
                    'language_id' => (int) $languageId,
                    'text' => (string) $text,
                ]);
            }
        }
    }

    protected function syncProductOptions(Product $product, Request $request): void
    {
        ProductOptionValue::query()->where('product_id', $product->id)->delete();
        ProductOption::query()->where('product_id', $product->id)->delete();

        $rows = $request->input('product_option', []);
        if (! is_array($rows)) {
            return;
        }

        $optionTypes = Option::query()->pluck('type', 'id');

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['option_id'])) {
                continue;
            }

            $optionId = (int) $row['option_id'];
            $type = $optionTypes[$optionId] ?? '';

            $productOption = ProductOption::create([
                'product_id' => $product->id,
                'option_id' => $optionId,
                'value' => (string) ($row['value'] ?? ''),
                'required' => ! empty($row['required']),
            ]);

            if (! in_array($type, ['select', 'radio', 'checkbox'], true)) {
                continue;
            }

            $values = $row['product_option_value'] ?? [];
            if (! is_array($values)) {
                continue;
            }

            foreach ($values as $valueRow) {
                if (! is_array($valueRow) || empty($valueRow['option_value_id'])) {
                    continue;
                }

                ProductOptionValue::create([
                    'product_option_id' => $productOption->id,
                    'product_id' => $product->id,
                    'option_id' => $optionId,
                    'option_value_id' => (int) $valueRow['option_value_id'],
                    'quantity' => (int) ($valueRow['quantity'] ?? 0),
                    'subtract' => ! empty($valueRow['subtract']),
                    'price' => (float) ($valueRow['price'] ?? 0),
                    'price_prefix' => in_array($valueRow['price_prefix'] ?? '+', ['+', '-'], true) ? $valueRow['price_prefix'] : '+',
                    'points' => (int) ($valueRow['points'] ?? 0),
                    'points_prefix' => in_array($valueRow['points_prefix'] ?? '+', ['+', '-'], true) ? $valueRow['points_prefix'] : '+',
                    'weight' => (float) ($valueRow['weight'] ?? 0),
                    'weight_prefix' => in_array($valueRow['weight_prefix'] ?? '+', ['+', '-'], true) ? $valueRow['weight_prefix'] : '+',
                ]);
            }
        }
    }

    protected function productDataRules(): array
    {
        return [
            'model' => 'required|string|max:64',
            'sku' => 'nullable|string|max:64',
            'upc' => 'nullable|string|max:12',
            'ean' => 'nullable|string|max:14',
            'jan' => 'nullable|string|max:13',
            'isbn' => 'nullable|string|max:17',
            'mpn' => 'nullable|string|max:64',
            'location' => 'nullable|string|max:128',
            'quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|string|max:255',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'shipping' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'points' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'minimum' => 'nullable|integer|min:1',
            'subtract' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
            'date_available' => 'nullable|date',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'product_image' => 'nullable|array',
            'product_image.*.image' => 'nullable|string|max:255',
            'product_image.*.sort_order' => 'nullable|integer|min:0',
        ];
    }

    protected function productDataFromRequest(Request $request): array
    {
        return [
            'model' => $request->model,
            'sku' => $request->input('sku'),
            'upc' => $request->input('upc'),
            'ean' => $request->input('ean'),
            'jan' => $request->input('jan'),
            'isbn' => $request->input('isbn'),
            'mpn' => $request->input('mpn'),
            'location' => $request->input('location'),
            'quantity' => (int) $request->input('quantity', 0),
            'image' => $request->input('image'),
            'manufacturer_id' => $request->input('manufacturer_id'),
            'shipping' => $request->boolean('shipping'),
            'price' => $request->input('price', 0),
            'points' => (int) $request->input('points', 0),
            'weight' => $request->input('weight', 0),
            'length' => $request->input('length', 0),
            'width' => $request->input('width', 0),
            'height' => $request->input('height', 0),
            'minimum' => (int) $request->input('minimum', 1),
            'subtract' => $request->boolean('subtract'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'status' => $request->boolean('status'),
            'date_available' => $request->input('date_available'),
        ];
    }

    protected function syncProductImages(Product $product, Request $request): void
    {
        ProductImage::query()->where('product_id', $product->id)->delete();

        $rows = $request->input('product_image', []);
        if (! is_array($rows)) {
            return;
        }

        $sort = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $image = trim((string) ($row['image'] ?? ''));
            if ($image === '') {
                continue;
            }

            ProductImage::create([
                'product_id' => $product->id,
                'image' => $image,
                'sort_order' => (int) ($row['sort_order'] ?? $sort),
            ]);
            $sort++;
        }
    }
}
