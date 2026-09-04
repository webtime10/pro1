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
 * 10 000 брюк (RU + EN), опции и атрибуты, фото catalog/104.webp.
 *
 * php artisan db:seed --class=BulkPantsSeeder
 * php artisan scout:import "App\Models\Product"
 */
class BulkPantsSeeder extends Seeder
{
    use EnsuresCatalogAttributes, EnsuresCatalogManufacturers;

    private const IMAGE_PATH = 'catalog/104.webp';

    private const TOTAL = 10000;

    private const CHUNK = 500;

    private const SKU_PREFIX = 'BULK-PANTS-';

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

            $stylesRu = ['Классические', 'Офисные', 'Чинос', 'Карго', 'Слим', 'Прямые', 'Костюмные', 'Летние', 'Premium', 'Casual'];
            $stylesEn = ['Classic', 'Office', 'Chinos', 'Cargo', 'Slim', 'Straight', 'Dress', 'Summer', 'Premium', 'Casual'];

            $materialsRu = ['Шерсть 100%', 'Шерсть с вискозой', 'Хлопок с эластаном', 'Полиэстер', 'Лён', 'Твид'];
            $materialsEn = ['100% wool', 'Wool with viscose', 'Cotton with elastane', 'Polyester', 'Linen', 'Tweed'];

            $risesRu = ['Низкая посадка', 'Средняя посадка', 'Высокая посадка'];
            $risesEn = ['Low rise', 'Mid rise', 'High rise'];

            $fitsRu = ['Slim', 'Regular', 'Relaxed', 'Tapered', 'Straight'];
            $fitsEn = ['Slim', 'Regular', 'Relaxed', 'Tapered', 'Straight'];

            $typesRu = ['Со стрелками', 'Без стрелок', 'С манжетами', 'Складки', 'Гладкие'];
            $typesEn = ['With crease', 'No crease', 'With cuffs', 'Pleated', 'Flat front'];

            for ($start = 1; $start <= self::TOTAL; $start += self::CHUNK) {
                $end = min($start + self::CHUNK - 1, self::TOTAL);

                DB::transaction(function () use (
                    $start, $end, $ru, $en, $category, $attributes, $options,
                    $stylesRu, $stylesEn, $materialsRu, $materialsEn,
                    $risesRu, $risesEn, $fitsRu, $fitsEn, $typesRu, $typesEn
                ) {
                    for ($i = $start; $i <= $end; $i++) {
                        $num = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                        $styleIdx = ($i - 1) % count($stylesRu);

                        $product = Product::create([
                            'model' => 'PANTS-'.$num,
                            'sku' => self::SKU_PREFIX.$num,
                            'quantity' => ($i % 11) + 2,
                            'image' => self::IMAGE_PATH,
                            'manufacturer_id' => $this->catalogManufacturerId($i),
                            'price' => 2490 + ($i % 45) * 70,
                            'sort_order' => $i,
                            'status' => true,
                            'shipping' => true,
                            'subtract' => true,
                            'minimum' => 1,
                        ]);

                        $product->categories()->sync([$category->id]);

                        $nameRu = $stylesRu[$styleIdx].' брюки #'.$i;
                        $nameEn = $stylesEn[$styleIdx].' pants #'.$i;

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $ru->id,
                            'name' => $nameRu,
                            'slug' => 'bryuki-'.$num,
                            'description' => '<p>Демо-брюки №'.$i.' ('.$stylesRu[$styleIdx].').</p>',
                            'tag' => 'брюки, демо, bulk',
                            'meta_title' => $nameRu,
                            'meta_description' => $nameRu,
                        ]);

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $en->id,
                            'name' => $nameEn,
                            'slug' => 'pants-'.$num,
                            'description' => '<p>Demo pants #'.$i.' ('.$stylesEn[$styleIdx].').</p>',
                            'tag' => 'pants, demo, bulk',
                            'meta_title' => $nameEn,
                            'meta_description' => $nameEn,
                        ]);

                        $attrData = [
                            'material' => [$materialsRu[$i % count($materialsRu)], $materialsEn[$i % count($materialsEn)]],
                            'rise' => [$risesRu[$i % count($risesRu)], $risesEn[$i % count($risesEn)]],
                            'fit' => [$fitsRu[$i % count($fitsRu)], $fitsEn[$i % count($fitsEn)]],
                            'type' => [$typesRu[$i % count($typesRu)], $typesEn[$i % count($typesEn)]],
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

                        $this->attachWaistOption($product, $options['waist'], $options['waist_values'], $i);
                        $this->attachLengthOption($product, $options['length'], $options['length_values'], $i);

                        if ($i % 2 !== 0) {
                            $this->attachColorOption($product, $options['color'], $options['color_values'], $i);
                        }
                    }
                });

                $this->command?->info("Создано товаров: {$end} / ".self::TOTAL);
            }
        });

        $this->command?->info('Готово: '.self::TOTAL.' брюк (RU + EN), SKU '.self::SKU_PREFIX.'00001 … '.self::SKU_PREFIX.'10000');
        $this->command?->line('Фото: /storage/'.self::IMAGE_PATH);
        $this->command?->line('Индекс: php artisan scout:import "App\Models\Product"');
    }

    private function ensureImage(): void
    {
        $public = public_path('catalog/104.webp');
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
            ->where('slug', 'bryuki-bulk')
            ->first();

        if ($existing) {
            return Category::findOrFail($existing->category_id);
        }

        $category = Category::create([
            'parent_id' => null,
            'image' => self::IMAGE_PATH,
            'top' => true,
            'column' => 1,
            'sort_order' => 4,
            'status' => true,
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $ru->id,
            'name' => 'Брюки (bulk)',
            'slug' => 'bryuki-bulk',
            'description' => '10000 демо-брюк',
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $en->id,
            'name' => 'Pants (bulk)',
            'slug' => 'pants-bulk',
            'description' => '10000 demo pants',
        ]);

        Category::rebuildPaths();

        return $category;
    }

    /** @return array<string, Attribute> */
    private function ensureAttributes(Language $ru, Language $en): array
    {
        $group = AttributeGroup::query()
            ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', 'Характеристики брюк'))
            ->first();

        if (! $group) {
            $group = AttributeGroup::create(['sort_order' => 4]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $ru->id,
                'name' => 'Характеристики брюк',
            ]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $en->id,
                'name' => 'Pants specifications',
            ]);
        }

        $defs = [
            'material' => ['Материал', 'Material'],
            'rise' => ['Посадка', 'Rise'],
            'fit' => ['Покрой', 'Fit'],
            'type' => ['Тип', 'Type'],
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
        $waist = $this->ensureOption($ru, $en, 'select', 'Размер (талия)', 'Waist size', 5);
        $length = $this->ensureOption($ru, $en, 'select', 'Длина', 'Inseam length', 6);
        $color = $this->ensureOption($ru, $en, 'radio', 'Цвет брюк', 'Pants color', 8);

        return [
            'waist' => $waist,
            'waist_values' => $this->ensureOptionValues(
                $waist, $ru, $en,
                ['28', '29', '30', '31', '32', '33', '34', '36', '38', '40']
            ),
            'length' => $length,
            'length_values' => $this->ensureOptionValues(
                $length, $ru, $en,
                ['30"', '32"', '34"', '36"'],
                ['30"', '32"', '34"', '36"']
            ),
            'color' => $color,
            'color_values' => $this->ensureOptionValues(
                $color, $ru, $en,
                ['Чёрный', 'Серый', 'Бежевый', 'Синий', 'Коричневый'],
                ['Black', 'Gray', 'Beige', 'Blue', 'Brown']
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

    /** @param  array<int, OptionValue>  $values */
    private function attachWaistOption(Product $product, Option $option, array $values, int $index): void
    {
        $offset = $index % 3;
        $count = 4 + ($index % 4);
        $subset = array_slice($values, $offset, min($count, count($values) - $offset));
        if ($subset === []) {
            $subset = array_slice($values, 0, 4);
        }

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
                'quantity' => 4 + (($index + $j) % 7),
                'subtract' => true,
                'price' => 0,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $values */
    private function attachLengthOption(Product $product, Option $option, array $values, int $index): void
    {
        $count = 2 + ($index % 3);
        $subset = array_slice($values, 0, min($count, count($values)));

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
                'quantity' => 6 + (($index + $j) % 5),
                'subtract' => true,
                'price' => $j * 100,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $values */
    private function attachColorOption(Product $product, Option $option, array $values, int $index): void
    {
        $count = 2 + ($index % 4);
        $subset = array_slice($values, 0, min($count, count($values)));

        $productOption = ProductOption::create([
            'product_id' => $product->id,
            'option_id' => $option->id,
            'value' => '',
            'required' => false,
        ]);

        foreach ($subset as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 3 + (($index + $j) % 6),
                'subtract' => true,
                'price' => $j * 75,
                'price_prefix' => '+',
            ]);
        }
    }
}
