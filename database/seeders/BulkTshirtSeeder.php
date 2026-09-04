<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeDescription;
use App\Models\AttributeGroup;
use App\Models\AttributeGroupDescription;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Language;
use App\Models\Option;
use App\Models\OptionDescription;
use App\Models\OptionValue;
use App\Models\OptionValueDescription;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductDescription;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Database\Seeders\Concerns\EnsuresCatalogAttributes;
use Database\Seeders\Concerns\EnsuresCatalogManufacturers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 10 000 футболок (RU + EN), опции и атрибуты, фото catalog/102.jpg.
 *
 * php artisan db:seed --class=BulkTshirtSeeder
 * php artisan scout:import "App\Models\Product"
 */
class BulkTshirtSeeder extends Seeder
{
    use EnsuresCatalogAttributes, EnsuresCatalogManufacturers;

    private const IMAGE_PATH = 'catalog/102.jpg';

    private const TOTAL = 10000;

    private const CHUNK = 250;

    private const SKU_PREFIX = 'BULK-TSHIRT-';

    public function run(): void
    {
        $ru = Language::query()->where('code', 'ru')->first();
        $en = Language::query()->where('code', 'en')->first();

        if (! $ru || ! $en) {
            $this->command?->error('Нужны языки ru и en. Запустите: php artisan db:seed --class=LanguageSeeder');

            return;
        }

        if (Product::query()->where('sku', 'like', self::SKU_PREFIX.'%')->exists()) {
            $this->command?->warn('Товары '.self::SKU_PREFIX.'* уже есть. Удалите их перед повторным запуском.');

            return;
        }

        $this->ensureImage();

        Product::withoutSyncingToSearch(function () use ($ru, $en) {
            $category = $this->ensureCategory($ru, $en);
            $attributes = $this->ensureAttributes($ru, $en);
            $options = $this->ensureOptions($ru, $en);

            $stylesRu = ['Базовая', 'Оверсайз', 'Спортивная', 'Премиум', 'Летняя', 'С принтом', 'Однотонная', 'Vintage', 'Urban', 'Classic'];
            $stylesEn = ['Basic', 'Oversize', 'Sport', 'Premium', 'Summer', 'Printed', 'Solid', 'Vintage', 'Urban', 'Classic'];

            $materialsRu = ['100% хлопок', 'Хлопок 95% / эластан 5%', 'Трикотаж', 'Хлопок пенье', 'Смесовая ткань', 'Органический хлопок'];
            $materialsEn = ['100% cotton', '95% cotton / 5% elastane', 'Jersey knit', 'Combed cotton', 'Blended fabric', 'Organic cotton'];

            $necklinesRu = ['Круглый вырез', 'V-образный', 'Поло', 'Лодочка'];
            $necklinesEn = ['Crew neck', 'V-neck', 'Polo', 'Boat neck'];

            $fitsRu = ['Regular', 'Slim', 'Oversize', 'Athletic', 'Relaxed'];
            $fitsEn = ['Regular', 'Slim', 'Oversize', 'Athletic', 'Relaxed'];

            $printsRu = ['Без принта', 'Логотип', 'Надпись', 'Полная печать', 'Минималистичный принт'];
            $printsEn = ['Plain', 'Logo', 'Text print', 'All-over print', 'Minimal print'];

            for ($start = 1; $start <= self::TOTAL; $start += self::CHUNK) {
                $end = min($start + self::CHUNK - 1, self::TOTAL);

                DB::transaction(function () use (
                    $start, $end, $ru, $en, $category, $attributes, $options,
                    $stylesRu, $stylesEn, $materialsRu, $materialsEn,
                    $necklinesRu, $necklinesEn, $fitsRu, $fitsEn, $printsRu, $printsEn
                ) {
                    for ($i = $start; $i <= $end; $i++) {
                        $num = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                        $styleIdx = ($i - 1) % count($stylesRu);

                        $product = Product::create([
                            'model' => 'TSHIRT-'.$num,
                            'sku' => self::SKU_PREFIX.$num,
                            'quantity' => ($i % 11) + 2,
                            'image' => self::IMAGE_PATH,
                            'manufacturer_id' => $this->catalogManufacturerId($i),
                            'price' => 590 + ($i % 40) * 50,
                            'sort_order' => $i,
                            'status' => true,
                            'shipping' => true,
                            'subtract' => true,
                            'minimum' => 1,
                        ]);

                        $product->categories()->sync([$category->id]);

                        $nameRu = $stylesRu[$styleIdx].' футболка #'.$i;
                        $nameEn = $stylesEn[$styleIdx].' t-shirt #'.$i;

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $ru->id,
                            'name' => $nameRu,
                            'slug' => 'futbolka-'.$num,
                            'description' => '<p>Демо-футболка №'.$i.' ('.$stylesRu[$styleIdx].').</p>',
                            'tag' => 'футболка, демо, bulk',
                            'meta_title' => $nameRu,
                            'meta_description' => $nameRu,
                        ]);

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $en->id,
                            'name' => $nameEn,
                            'slug' => 'tshirt-'.$num,
                            'description' => '<p>Demo t-shirt #'.$i.' ('.$stylesEn[$styleIdx].').</p>',
                            'tag' => 't-shirt, demo, bulk',
                            'meta_title' => $nameEn,
                            'meta_description' => $nameEn,
                        ]);

                        $attrData = [
                            'material' => [$materialsRu[$i % count($materialsRu)], $materialsEn[$i % count($materialsEn)]],
                            'neckline' => [$necklinesRu[$i % count($necklinesRu)], $necklinesEn[$i % count($necklinesEn)]],
                            'fit' => [$fitsRu[$i % count($fitsRu)], $fitsEn[$i % count($fitsEn)]],
                            'print' => [$printsRu[$i % count($printsRu)], $printsEn[$i % count($printsEn)]],
                        ];

                        foreach ($attrData as $key => [$textRu, $textEn]) {
                            ProductAttribute::create([
                                'product_id' => $product->id,
                                'attribute_id' => $attributes[$key]->id,
                                'language_id' => $ru->id,
                                'text' => $textRu,
                            ]);
                            ProductAttribute::create([
                                'product_id' => $product->id,
                                'attribute_id' => $attributes[$key]->id,
                                'language_id' => $en->id,
                                'text' => $textEn,
                            ]);
                        }

                        $this->attachSizeOption($product, $options['size'], $options['size_values'], $i);
                        $this->attachColorOption($product, $options['color'], $options['color_values'], $i);

                        if ($i % 4 === 0) {
                            $this->attachWeightOption($product, $options['weight'], $options['weight_values'], $i);
                        }
                    }
                });

                $this->command?->info("Создано товаров: {$end} / ".self::TOTAL);
            }
        });

        $this->command?->info('Готово: '.self::TOTAL.' футболок (RU + EN), SKU '.self::SKU_PREFIX.'00001 … '.self::SKU_PREFIX.'10000');
        $this->command?->line('Фото: /storage/'.self::IMAGE_PATH);
        $this->command?->line('Индекс: php artisan scout:import "App\Models\Product"');
    }

    private function ensureImage(): void
    {
        $public = public_path('catalog/102.jpg');
        $storage = storage_path('app/public/'.self::IMAGE_PATH);

        @mkdir(dirname($public), 0755, true);
        @mkdir(dirname($storage), 0755, true);

        if (is_file($public) && ! is_file($storage)) {
            copy($public, $storage);

            return;
        }

        if (is_file($storage) && ! is_file($public)) {
            copy($storage, $public);
        }
    }

    private function ensureCategory(Language $ru, Language $en): Category
    {
        $existing = CategoryDescription::query()
            ->where('language_id', $ru->id)
            ->where('slug', 'futbolki-bulk')
            ->first();

        if ($existing) {
            return Category::findOrFail($existing->category_id);
        }

        $category = Category::create([
            'parent_id' => null,
            'image' => self::IMAGE_PATH,
            'top' => true,
            'column' => 1,
            'sort_order' => 2,
            'status' => true,
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $ru->id,
            'name' => 'Футболки (bulk)',
            'slug' => 'futbolki-bulk',
            'description' => '10000 демо-футболок',
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $en->id,
            'name' => 'T-shirts (bulk)',
            'slug' => 'tshirts-bulk',
            'description' => '10000 demo t-shirts',
        ]);

        Category::rebuildPaths();

        return $category;
    }

    /** @return array<string, Attribute> */
    private function ensureAttributes(Language $ru, Language $en): array
    {
        $group = AttributeGroup::query()
            ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', 'Характеристики футболки'))
            ->first();

        if (! $group) {
            $group = AttributeGroup::create(['sort_order' => 2]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $ru->id,
                'name' => 'Характеристики футболки',
            ]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $en->id,
                'name' => 'T-shirt specifications',
            ]);
        }

        $defs = [
            'material' => ['Материал', 'Material'],
            'neckline' => ['Вырез', 'Neckline'],
            'fit' => ['Покрой', 'Fit'],
            'print' => ['Принт', 'Print'],
        ];

        $result = [];
        foreach ($defs as $key => [$nameRu, $nameEn]) {
            $result[$key] = $this->firstOrCreateAttributeByName(
                $group,
                $ru,
                $nameRu,
                $en,
                $nameEn,
                count($result) + 1
            );
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function ensureOptions(Language $ru, Language $en): array
    {
        $size = $this->ensureOption($ru, $en, 'select', 'Размер', 'Size', 1);
        $color = $this->ensureOption($ru, $en, 'radio', 'Цвет', 'Color', 2);
        $weight = $this->ensureOption($ru, $en, 'select', 'Плотность ткани', 'Fabric weight', 4);

        return [
            'size' => $size,
            'size_values' => $this->ensureOptionValues($size, $ru, $en, ['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'color' => $color,
            'color_values' => $this->ensureOptionValues(
                $color, $ru, $en,
                ['Белый', 'Чёрный', 'Серый', 'Красный', 'Синий', 'Зелёный'],
                ['White', 'Black', 'Gray', 'Red', 'Blue', 'Green']
            ),
            'weight' => $weight,
            'weight_values' => $this->ensureOptionValues(
                $weight, $ru, $en,
                ['Лёгкая', 'Средняя', 'Плотная'],
                ['Light', 'Medium', 'Heavy']
            ),
        ];
    }

    private function ensureOption(Language $ru, Language $en, string $type, string $nameRu, string $nameEn, int $sort): Option
    {
        $option = Option::query()
            ->where('type', $type)
            ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', $nameRu))
            ->first();

        if ($option) {
            return $option;
        }

        $option = Option::create(['type' => $type, 'sort_order' => $sort]);
        OptionDescription::create(['option_id' => $option->id, 'language_id' => $ru->id, 'name' => $nameRu]);
        OptionDescription::create(['option_id' => $option->id, 'language_id' => $en->id, 'name' => $nameEn]);

        return $option;
    }

    /**
     * @param  array<int, string>  $namesRu
     * @param  array<int, string>|null  $namesEn
     * @return array<int, OptionValue>
     */
    private function ensureOptionValues(Option $option, Language $ru, Language $en, array $namesRu, ?array $namesEn = null): array
    {
        $namesEn = $namesEn ?? $namesRu;
        $values = [];

        foreach ($namesRu as $i => $nameRu) {
            $nameEn = $namesEn[$i] ?? $nameRu;

            $value = OptionValue::query()
                ->where('option_id', $option->id)
                ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', $nameRu))
                ->first();

            if (! $value) {
                $value = OptionValue::create([
                    'option_id' => $option->id,
                    'image' => '',
                    'sort_order' => $i + 1,
                ]);
                OptionValueDescription::create([
                    'option_value_id' => $value->id,
                    'language_id' => $ru->id,
                    'option_id' => $option->id,
                    'name' => $nameRu,
                ]);
                OptionValueDescription::create([
                    'option_value_id' => $value->id,
                    'language_id' => $en->id,
                    'option_id' => $option->id,
                    'name' => $nameEn,
                ]);
            }

            $values[] = $value;
        }

        return $values;
    }

    /** @param  array<int, OptionValue>  $sizeValues */
    private function attachSizeOption(Product $product, Option $option, array $sizeValues, int $index): void
    {
        $count = 3 + ($index % 4);
        $subset = array_slice($sizeValues, 0, min($count, count($sizeValues)));

        $productOption = ProductOption::create([
            'product_id' => $product->id,
            'option_id' => $option->id,
            'value' => '',
            'required' => true,
        ]);

        foreach ($subset as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 4 + (($index + $j) % 12),
                'subtract' => true,
                'price' => 0,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $colorValues */
    private function attachColorOption(Product $product, Option $option, array $colorValues, int $index): void
    {
        $count = 2 + ($index % 4);
        $subset = array_slice($colorValues, 0, min($count, count($colorValues)));

        $productOption = ProductOption::create([
            'product_id' => $product->id,
            'option_id' => $option->id,
            'value' => '',
            'required' => $index % 3 !== 0,
        ]);

        foreach ($subset as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 2 + (($index + $j) % 9),
                'subtract' => true,
                'price' => $j * 50,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $weightValues */
    private function attachWeightOption(Product $product, Option $option, array $weightValues, int $index): void
    {
        $productOption = ProductOption::create([
            'product_id' => $product->id,
            'option_id' => $option->id,
            'value' => '',
            'required' => false,
        ]);

        foreach ($weightValues as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 8,
                'subtract' => false,
                'price' => $j * 80,
                'price_prefix' => '+',
            ]);
        }
    }
}
