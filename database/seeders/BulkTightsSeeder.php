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
 * 10 000 чулок / колготок (RU + EN), опции и атрибуты, фото catalog/108.png.
 *
 * php artisan db:seed --class=BulkTightsSeeder
 * php artisan scout:import "App\Models\Product"
 */
class BulkTightsSeeder extends Seeder
{
    use EnsuresCatalogAttributes, EnsuresCatalogManufacturers;

    private const IMAGE_PATH = 'catalog/108.png';

    private const TOTAL = 10000;

    private const CHUNK = 500;

    private const SKU_PREFIX = 'BULK-TIGHTS-';

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

            $stylesRu = ['Классические', 'Матовые', 'Глянцевые', 'С рисунком', 'Компрессионные', 'Утеплённые', 'Сетчатые', 'Premium', 'Офисные', 'Повседневные'];
            $stylesEn = ['Classic', 'Matte', 'Glossy', 'Patterned', 'Compression', 'Thermal', 'Fishnet', 'Premium', 'Office', 'Everyday'];

            $materialsRu = ['Нейлон', 'Микрофибра', 'Хлопок с эластаном', 'Полиамид', 'Смесовая нить', 'Органический хлопок'];
            $materialsEn = ['Nylon', 'Microfiber', 'Cotton with elastane', 'Polyamide', 'Blended yarn', 'Organic cotton'];

            $deniersRu = ['15 den', '20 den', '40 den', '60 den', '80 den', '100 den'];
            $deniersEn = ['15 den', '20 den', '40 den', '60 den', '80 den', '100 den'];

            $typesRu = ['Колготки', 'Чулки', 'Гольфы', 'Леггинсы', 'Капроновые'];
            $typesEn = ['Tights', 'Stockings', 'Knee-highs', 'Leggings', 'Sheer hosiery'];

            $careRu = ['Ручная стирка', 'Деликатная стирка 30°C', 'Не отбеливать', 'Не сушить в барабане'];
            $careEn = ['Hand wash', 'Delicate wash 30°C', 'Do not bleach', 'Do not tumble dry'];

            for ($start = 1; $start <= self::TOTAL; $start += self::CHUNK) {
                $end = min($start + self::CHUNK - 1, self::TOTAL);

                DB::transaction(function () use (
                    $start, $end, $ru, $en, $category, $attributes, $options,
                    $stylesRu, $stylesEn, $materialsRu, $materialsEn,
                    $deniersRu, $deniersEn, $typesRu, $typesEn, $careRu, $careEn
                ) {
                    for ($i = $start; $i <= $end; $i++) {
                        $num = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                        $styleIdx = ($i - 1) % count($stylesRu);

                        $product = Product::create([
                            'model' => 'TIGHTS-'.$num,
                            'sku' => self::SKU_PREFIX.$num,
                            'quantity' => ($i % 12) + 4,
                            'image' => self::IMAGE_PATH,
                            'manufacturer_id' => $this->catalogManufacturerId($i),
                            'price' => 490 + ($i % 30) * 35,
                            'sort_order' => $i,
                            'status' => true,
                            'shipping' => true,
                            'subtract' => true,
                            'minimum' => 1,
                        ]);

                        $product->categories()->sync([$category->id]);

                        $nameRu = $stylesRu[$styleIdx].' чулки #'.$i;
                        $nameEn = $stylesEn[$styleIdx].' tights #'.$i;

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $ru->id,
                            'name' => $nameRu,
                            'slug' => 'chulki-'.$num,
                            'description' => '<p>Демо-чулки №'.$i.' ('.$stylesRu[$styleIdx].').</p>',
                            'tag' => 'чулки, колготки, демо, bulk',
                            'meta_title' => $nameRu,
                            'meta_description' => $nameRu,
                        ]);

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $en->id,
                            'name' => $nameEn,
                            'slug' => 'tights-'.$num,
                            'description' => '<p>Demo tights #'.$i.' ('.$stylesEn[$styleIdx].').</p>',
                            'tag' => 'tights, stockings, demo, bulk',
                            'meta_title' => $nameEn,
                            'meta_description' => $nameEn,
                        ]);

                        $attrData = [
                            'material' => [$materialsRu[$i % count($materialsRu)], $materialsEn[$i % count($materialsEn)]],
                            'denier' => [$deniersRu[$i % count($deniersRu)], $deniersEn[$i % count($deniersEn)]],
                            'type' => [$typesRu[$i % count($typesRu)], $typesEn[$i % count($typesEn)]],
                            'care' => [$careRu[$i % count($careRu)], $careEn[$i % count($careEn)]],
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
                            $this->attachOpacityOption($product, $options['opacity'], $options['opacity_values'], $i);
                        }
                    }
                });

                $this->command?->info("Создано товаров: {$end} / ".self::TOTAL);
            }
        });

        $this->command?->info('Готово: '.self::TOTAL.' чулок (RU + EN), SKU '.self::SKU_PREFIX.'00001 … '.self::SKU_PREFIX.'10000');
        $this->command?->line('Фото: /storage/'.self::IMAGE_PATH);
        $this->command?->line('Индекс: php artisan scout:import "App\Models\Product"');
    }

    private function ensureImage(): void
    {
        $public = public_path('catalog/108.png');
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
            ->where('slug', 'chulki-bulk')
            ->first();

        if ($existing) {
            return Category::findOrFail($existing->category_id);
        }

        $category = Category::create([
            'parent_id' => null,
            'image' => self::IMAGE_PATH,
            'top' => true,
            'column' => 1,
            'sort_order' => 7,
            'status' => true,
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $ru->id,
            'name' => 'Чулки (bulk)',
            'slug' => 'chulki-bulk',
            'description' => '10000 демо-чулок и колготок',
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $en->id,
            'name' => 'Tights & stockings (bulk)',
            'slug' => 'tights-bulk',
            'description' => '10000 demo tights and stockings',
        ]);

        Category::rebuildPaths();

        return $category;
    }

    /** @return array<string, Attribute> */
    private function ensureAttributes(Language $ru, Language $en): array
    {
        $group = AttributeGroup::query()
            ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', 'Характеристики чулок'))
            ->first();

        if (! $group) {
            $group = AttributeGroup::create(['sort_order' => 7]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $ru->id,
                'name' => 'Характеристики чулок',
            ]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $en->id,
                'name' => 'Hosiery specifications',
            ]);
        }

        $defs = [
            'material' => ['Материал', 'Material'],
            'denier' => ['Плотность', 'Denier'],
            'type' => ['Тип', 'Type'],
            'care' => ['Уход', 'Care'],
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
        $size = $this->ensureOption($ru, $en, 'select', 'Размер чулок', 'Hosiery size', 15);
        $color = $this->ensureOption($ru, $en, 'radio', 'Цвет чулок', 'Hosiery color', 16);
        $opacity = $this->ensureOption($ru, $en, 'select', 'Прозрачность', 'Opacity', 17);

        return [
            'size' => $size,
            'size_values' => $this->ensureOptionValues(
                $size, $ru, $en,
                ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                ['XS', 'S', 'M', 'L', 'XL', 'XXL']
            ),
            'color' => $color,
            'color_values' => $this->ensureOptionValues(
                $color, $ru, $en,
                ['Чёрный', 'Телесный', 'Серый', 'Синий', 'Бордовый', 'Белый'],
                ['Black', 'Nude', 'Gray', 'Blue', 'Burgundy', 'White']
            ),
            'opacity' => $opacity,
            'opacity_values' => $this->ensureOptionValues(
                $opacity, $ru, $en,
                ['Прозрачные', 'Полупрозрачные', 'Плотные'],
                ['Sheer', 'Semi-opaque', 'Opaque']
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
    private function attachSizeOption(Product $product, Option $option, array $values, int $index): void
    {
        $count = 3 + ($index % 4);
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
                'quantity' => 6 + (($index + $j) % 10),
                'subtract' => true,
                'price' => 0,
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
            'required' => $index % 3 !== 0,
        ]);

        foreach ($subset as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 4 + (($index + $j) % 8),
                'subtract' => true,
                'price' => $j * 25,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $values */
    private function attachOpacityOption(Product $product, Option $option, array $values, int $index): void
    {
        $productOption = ProductOption::create([
            'product_id' => $product->id,
            'option_id' => $option->id,
            'value' => '',
            'required' => false,
        ]);

        foreach ($values as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 12,
                'subtract' => false,
                'price' => $j * 40,
                'price_prefix' => '+',
            ]);
        }
    }
}
