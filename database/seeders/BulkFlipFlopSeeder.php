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
 * 10 000 вьетнамок (RU + EN), опции и атрибуты, фото catalog/111.png.
 *
 * php artisan db:seed --class=BulkFlipFlopSeeder
 * php artisan scout:import "App\Models\Product"
 * php artisan catalog:unique-product-images
 */
class BulkFlipFlopSeeder extends Seeder
{
    use EnsuresCatalogAttributes, EnsuresCatalogManufacturers;

    private const IMAGE_PATH = 'catalog/111.png';

    private const TOTAL = 10000;

    private const CHUNK = 500;

    private const SKU_PREFIX = 'BULK-FLIPFLOP-';

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

            $stylesRu = ['Классические', 'Пляжные', 'Спортивные', 'Массажные', 'Анатомические', 'Детские', 'Женские', 'Мужские', 'Premium', 'Лёгкие'];
            $stylesEn = ['Classic', 'Beach', 'Sport', 'Massage', 'Anatomical', 'Kids', 'Women', 'Men', 'Premium', 'Lightweight'];

            $materialsRu = ['Резина', 'EVA', 'ПВХ', 'Каучук', 'Силикон', 'Пена'];
            $materialsEn = ['Rubber', 'EVA', 'PVC', 'Natural rubber', 'Silicone', 'Foam'];

            $strapsRu = ['Тонкий ремешок', 'Широкий ремешок', 'Мягкий ремешок', 'С логотипом', 'Двойной ремешок'];
            $strapsEn = ['Thin strap', 'Wide strap', 'Soft strap', 'Logo strap', 'Double strap'];

            $solesRu = ['Плоская', 'Рифлёная', 'Антискользящая', 'Массажная', 'Мягкая пена'];
            $solesEn = ['Flat', 'Grooved', 'Non-slip', 'Massage', 'Soft foam'];

            $seasonsRu = ['Лето', 'Пляж', 'Бассейн', 'Всесезонные'];
            $seasonsEn = ['Summer', 'Beach', 'Pool', 'All-season'];

            for ($start = 1; $start <= self::TOTAL; $start += self::CHUNK) {
                $end = min($start + self::CHUNK - 1, self::TOTAL);

                DB::transaction(function () use (
                    $start, $end, $ru, $en, $category, $attributes, $options,
                    $stylesRu, $stylesEn, $materialsRu, $materialsEn,
                    $strapsRu, $strapsEn, $solesRu, $solesEn, $seasonsRu, $seasonsEn
                ) {
                    for ($i = $start; $i <= $end; $i++) {
                        $num = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                        $styleIdx = ($i - 1) % count($stylesRu);

                        $product = Product::create([
                            'model' => 'FLP-'.$num,
                            'sku' => self::SKU_PREFIX.$num,
                            'quantity' => ($i % 25) + 5,
                            'image' => self::IMAGE_PATH,
                            'manufacturer_id' => $this->catalogManufacturerId($i),
                            'price' => 290 + ($i % 30) * 35,
                            'sort_order' => $i,
                            'status' => true,
                            'shipping' => true,
                            'subtract' => true,
                            'minimum' => 1,
                        ]);

                        $product->categories()->sync([$category->id]);

                        $nameRu = $stylesRu[$styleIdx].' вьетнамки #'.$i;
                        $nameEn = $stylesEn[$styleIdx].' flip-flops #'.$i;

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $ru->id,
                            'name' => $nameRu,
                            'slug' => 'vetnamki-'.$num,
                            'description' => '<p>Демо-вьетнамки №'.$i.' ('.$stylesRu[$styleIdx].').</p>',
                            'tag' => 'вьетнамки, шлёпанцы, демо, bulk',
                            'meta_title' => $nameRu,
                            'meta_description' => $nameRu,
                        ]);

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $en->id,
                            'name' => $nameEn,
                            'slug' => 'flip-flops-'.$num,
                            'description' => '<p>Demo flip-flops #'.$i.' ('.$stylesEn[$styleIdx].').</p>',
                            'tag' => 'flip-flops, sandals, demo, bulk',
                            'meta_title' => $nameEn,
                            'meta_description' => $nameEn,
                        ]);

                        $attrData = [
                            'material' => [$materialsRu[$i % count($materialsRu)], $materialsEn[$i % count($materialsEn)]],
                            'strap' => [$strapsRu[$i % count($strapsRu)], $strapsEn[$i % count($strapsEn)]],
                            'sole' => [$solesRu[$i % count($solesRu)], $solesEn[$i % count($solesEn)]],
                            'season' => [$seasonsRu[$i % count($seasonsRu)], $seasonsEn[$i % count($seasonsEn)]],
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
                            $this->attachPackOption($product, $options['pack'], $options['pack_values'], $i);
                        }
                    }
                });

                $this->command?->info("Создано товаров: {$end} / ".self::TOTAL);
            }
        });

        $this->command?->info('Готово: '.self::TOTAL.' вьетнамок (RU + EN), SKU '.self::SKU_PREFIX.'00001 … '.self::SKU_PREFIX.'10000');
        $this->command?->line('Фото: /storage/'.self::IMAGE_PATH);
        $this->command?->line('Индекс: php artisan scout:import "App\Models\Product"');
        $this->command?->line('Уникальные фото: php artisan catalog:unique-product-images');
    }

    private function ensureImage(): void
    {
        $public = public_path('catalog/111.png');
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

        if (! is_file($storage) && ! is_file($public)) {
            $this->command?->error('Нет фото '.self::IMAGE_PATH.' — положите файл в storage/app/public/catalog/111.png');
        }
    }

    private function ensureCategory(Language $ru, Language $en): Category
    {
        $existing = CategoryDescription::query()
            ->where('language_id', $ru->id)
            ->where('slug', 'vetnamki-bulk')
            ->first();

        if ($existing) {
            return Category::findOrFail($existing->category_id);
        }

        $category = Category::create([
            'parent_id' => null,
            'image' => self::IMAGE_PATH,
            'top' => true,
            'column' => 1,
            'sort_order' => 11,
            'status' => true,
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $ru->id,
            'name' => 'Вьетнамки (bulk)',
            'slug' => 'vetnamki-bulk',
            'description' => '10000 демо-вьетнамок',
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $en->id,
            'name' => 'Flip-flops (bulk)',
            'slug' => 'flip-flops-bulk',
            'description' => '10000 demo flip-flops',
        ]);

        Category::rebuildPaths();

        return $category;
    }

    /** @return array<string, Attribute> */
    private function ensureAttributes(Language $ru, Language $en): array
    {
        $group = AttributeGroup::query()
            ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', 'Характеристики вьетнамок'))
            ->first();

        if (! $group) {
            $group = AttributeGroup::create(['sort_order' => 11]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $ru->id,
                'name' => 'Характеристики вьетнамок',
            ]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $en->id,
                'name' => 'Flip-flop specifications',
            ]);
        }

        $defs = [
            'material' => ['Материал', 'Material'],
            'strap' => ['Ремешок', 'Strap'],
            'sole' => ['Подошва', 'Sole'],
            'season' => ['Назначение', 'Purpose'],
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
        $size = $this->ensureOption($ru, $en, 'select', 'Размер вьетнамок', 'Flip-flop size', 30);
        $color = $this->ensureOption($ru, $en, 'radio', 'Цвет вьетнамок', 'Flip-flop color', 31);
        $pack = $this->ensureOption($ru, $en, 'select', 'Комплект', 'Pack', 32);

        return [
            'size' => $size,
            'size_values' => $this->ensureOptionValues(
                $size, $ru, $en,
                ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'],
                ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45']
            ),
            'color' => $color,
            'color_values' => $this->ensureOptionValues(
                $color, $ru, $en,
                ['Чёрный', 'Белый', 'Синий', 'Красный', 'Жёлтый', 'Зелёный', 'Розовый'],
                ['Black', 'White', 'Blue', 'Red', 'Yellow', 'Green', 'Pink']
            ),
            'pack' => $pack,
            'pack_values' => $this->ensureOptionValues(
                $pack, $ru, $en,
                ['1 пара', '2 пары', '3 пары'],
                ['1 pair', '2 pairs', '3 pairs']
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
        $count = 3 + ($index % 5);
        $offset = $index % max(1, count($values) - $count);
        $subset = array_slice($values, $offset, min($count, count($values)));

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
                'quantity' => 8 + (($index + $j) % 20),
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
                'quantity' => 6 + (($index + $j) % 12),
                'subtract' => true,
                'price' => $j * 20,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $values */
    private function attachPackOption(Product $product, Option $option, array $values, int $index): void
    {
        $count = 2 + ($index % 2);
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
                'quantity' => 30,
                'subtract' => false,
                'price' => $j * 200,
                'price_prefix' => '+',
            ]);
        }
    }
}
