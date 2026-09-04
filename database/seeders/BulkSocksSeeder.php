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
 * 10 000 носков (RU + EN), опции и атрибуты, фото catalog/107.png.
 *
 * php artisan db:seed --class=BulkSocksSeeder
 * php artisan scout:import "App\Models\Product"
 */
class BulkSocksSeeder extends Seeder
{
    use EnsuresCatalogAttributes, EnsuresCatalogManufacturers;

    private const IMAGE_PATH = 'catalog/107.png';

    private const TOTAL = 10000;

    private const CHUNK = 500;

    private const SKU_PREFIX = 'BULK-SOCKS-';

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

            $stylesRu = ['Классические', 'Спортивные', 'Невидимки', 'Тёплые', 'Бизнес', 'Уличные', 'Компрессионные', 'Детские', 'Premium', 'Набор 3 пары'];
            $stylesEn = ['Classic', 'Sport', 'No-show', 'Warm', 'Business', 'Street', 'Compression', 'Kids', 'Premium', '3-pack'];

            $materialsRu = ['100% хлопок', 'Хлопок с эластаном', 'Бамбук', 'Шерсть', 'Полиэстер', 'Смесовая пряжа'];
            $materialsEn = ['100% cotton', 'Cotton with elastane', 'Bamboo', 'Wool', 'Polyester', 'Blended yarn'];

            $heightsRu = ['Короткие', 'Средние', 'Высокие', 'До колена'];
            $heightsEn = ['Ankle', 'Crew', 'High', 'Knee-high'];

            $purposesRu = ['Повседневные', 'Для бега', 'Для офиса', 'Для зала', 'Домашние'];
            $purposesEn = ['Everyday', 'Running', 'Office', 'Gym', 'Home'];

            $careRu = ['Машинная стирка 40°C', 'Деликатная стирка', 'Не отбеливать', 'Сушка в расправленном виде'];
            $careEn = ['Machine wash 40°C', 'Delicate wash', 'Do not bleach', 'Dry flat'];

            for ($start = 1; $start <= self::TOTAL; $start += self::CHUNK) {
                $end = min($start + self::CHUNK - 1, self::TOTAL);

                DB::transaction(function () use (
                    $start, $end, $ru, $en, $category, $attributes, $options,
                    $stylesRu, $stylesEn, $materialsRu, $materialsEn,
                    $heightsRu, $heightsEn, $purposesRu, $purposesEn, $careRu, $careEn
                ) {
                    for ($i = $start; $i <= $end; $i++) {
                        $num = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
                        $styleIdx = ($i - 1) % count($stylesRu);

                        $product = Product::create([
                            'model' => 'SOCKS-'.$num,
                            'sku' => self::SKU_PREFIX.$num,
                            'quantity' => ($i % 20) + 10,
                            'image' => self::IMAGE_PATH,
                            'manufacturer_id' => $this->catalogManufacturerId($i),
                            'price' => 290 + ($i % 20) * 25,
                            'sort_order' => $i,
                            'status' => true,
                            'shipping' => true,
                            'subtract' => true,
                            'minimum' => 1,
                        ]);

                        $product->categories()->sync([$category->id]);

                        $nameRu = $stylesRu[$styleIdx].' носки #'.$i;
                        $nameEn = $stylesEn[$styleIdx].' socks #'.$i;

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $ru->id,
                            'name' => $nameRu,
                            'slug' => 'noski-'.$num,
                            'description' => '<p>Демо-носки №'.$i.' ('.$stylesRu[$styleIdx].').</p>',
                            'tag' => 'носки, демо, bulk',
                            'meta_title' => $nameRu,
                            'meta_description' => $nameRu,
                        ]);

                        ProductDescription::create([
                            'product_id' => $product->id,
                            'language_id' => $en->id,
                            'name' => $nameEn,
                            'slug' => 'socks-'.$num,
                            'description' => '<p>Demo socks #'.$i.' ('.$stylesEn[$styleIdx].').</p>',
                            'tag' => 'socks, demo, bulk',
                            'meta_title' => $nameEn,
                            'meta_description' => $nameEn,
                        ]);

                        $attrData = [
                            'material' => [$materialsRu[$i % count($materialsRu)], $materialsEn[$i % count($materialsEn)]],
                            'height' => [$heightsRu[$i % count($heightsRu)], $heightsEn[$i % count($heightsEn)]],
                            'purpose' => [$purposesRu[$i % count($purposesRu)], $purposesEn[$i % count($purposesEn)]],
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

                        if ($i % 5 === 0) {
                            $this->attachPackOption($product, $options['pack'], $options['pack_values'], $i);
                        }
                    }
                });

                $this->command?->info("Создано товаров: {$end} / ".self::TOTAL);
            }
        });

        $this->command?->info('Готово: '.self::TOTAL.' носков (RU + EN), SKU '.self::SKU_PREFIX.'00001 … '.self::SKU_PREFIX.'10000');
        $this->command?->line('Фото: /storage/'.self::IMAGE_PATH);
        $this->command?->line('Индекс: php artisan scout:import "App\Models\Product"');
    }

    private function ensureImage(): void
    {
        $public = public_path('catalog/107.png');
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
            ->where('slug', 'noski-bulk')
            ->first();

        if ($existing) {
            return Category::findOrFail($existing->category_id);
        }

        $category = Category::create([
            'parent_id' => null,
            'image' => self::IMAGE_PATH,
            'top' => true,
            'column' => 1,
            'sort_order' => 6,
            'status' => true,
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $ru->id,
            'name' => 'Носки (bulk)',
            'slug' => 'noski-bulk',
            'description' => '10000 демо-носков',
        ]);

        CategoryDescription::create([
            'category_id' => $category->id,
            'language_id' => $en->id,
            'name' => 'Socks (bulk)',
            'slug' => 'socks-bulk',
            'description' => '10000 demo socks',
        ]);

        Category::rebuildPaths();

        return $category;
    }

    /** @return array<string, Attribute> */
    private function ensureAttributes(Language $ru, Language $en): array
    {
        $group = AttributeGroup::query()
            ->whereHas('descriptions', fn ($q) => $q->where('language_id', $ru->id)->where('name', 'Характеристики носков'))
            ->first();

        if (! $group) {
            $group = AttributeGroup::create(['sort_order' => 6]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $ru->id,
                'name' => 'Характеристики носков',
            ]);
            AttributeGroupDescription::create([
                'attribute_group_id' => $group->id,
                'language_id' => $en->id,
                'name' => 'Socks specifications',
            ]);
        }

        $defs = [
            'material' => ['Материал', 'Material'],
            'height' => ['Высота', 'Height'],
            'purpose' => ['Назначение', 'Purpose'],
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
        $size = $this->ensureOption($ru, $en, 'select', 'Размер носков', 'Sock size', 12);
        $color = $this->ensureOption($ru, $en, 'radio', 'Цвет носков', 'Sock color', 13);
        $pack = $this->ensureOption($ru, $en, 'select', 'Упаковка', 'Pack', 14);

        return [
            'size' => $size,
            'size_values' => $this->ensureOptionValues(
                $size, $ru, $en,
                ['35-38', '39-42', '43-46', '47-50'],
                ['35-38', '39-42', '43-46', '47-50']
            ),
            'color' => $color,
            'color_values' => $this->ensureOptionValues(
                $color, $ru, $en,
                ['Чёрный', 'Белый', 'Серый', 'Синий', 'Красный', 'Бежевый'],
                ['Black', 'White', 'Gray', 'Blue', 'Red', 'Beige']
            ),
            'pack' => $pack,
            'pack_values' => $this->ensureOptionValues(
                $pack, $ru, $en,
                ['1 пара', '3 пары', '5 пар'],
                ['1 pair', '3 pairs', '5 pairs']
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
                'quantity' => 15 + (($index + $j) % 20),
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
            'required' => $index % 2 === 0,
        ]);

        foreach ($subset as $j => $value) {
            ProductOptionValue::create([
                'product_option_id' => $productOption->id,
                'product_id' => $product->id,
                'option_id' => $option->id,
                'option_value_id' => $value->id,
                'quantity' => 10 + (($index + $j) % 15),
                'subtract' => true,
                'price' => $j * 15,
                'price_prefix' => '+',
            ]);
        }
    }

    /** @param  array<int, OptionValue>  $values */
    private function attachPackOption(Product $product, Option $option, array $values, int $index): void
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
                'quantity' => 30,
                'subtract' => false,
                'price' => $j * 120,
                'price_prefix' => '+',
            ]);
        }
    }
}
