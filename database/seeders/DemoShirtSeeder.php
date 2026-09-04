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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\EnsuresCatalogAttributes;
use Database\Seeders\Concerns\EnsuresCatalogManufacturers;

/**
 * 20 пробных рубашек с категорией, атрибутами и опциями (размер / цвет).
 *
 * Запуск: php artisan db:seed --class=DemoShirtSeeder
 * После:  php artisan scout:import "App\Models\Product"
 */
class DemoShirtSeeder extends Seeder
{
    use EnsuresCatalogAttributes, EnsuresCatalogManufacturers;

    private const IMAGE_PATH = 'catalog/demo/shirt.jpg';

    public function run(): void
    {
        $defaultLang = Language::getDefault();
        if (! $defaultLang) {
            $this->command?->error('Нет языка по умолчанию. Сначала: php artisan db:seed --class=LanguageSeeder');

            return;
        }

        if (Product::query()->where('sku', 'like', 'DEMO-SHIRT-%')->exists()) {
            $this->command?->warn('Пробные рубашки уже есть (SKU DEMO-SHIRT-*). Пропуск.');

            return;
        }

        $this->ensureDemoImage();

    DB::transaction(function () use ($defaultLang) {
      $category = $this->ensureCategory($defaultLang);
      $attributes = $this->ensureAttributes($defaultLang);
      [$sizeOption, $sizeValues] = $this->ensureSizeOption($defaultLang);
      [$colorOption, $colorValues] = $this->ensureColorOption($defaultLang);

      $shirts = [
        ['name' => 'Классическая рубашка Oxford', 'model' => 'OXF-001', 'price' => 2490, 'material' => '100% хлопок Oxford', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Рубашка в тонкую полоску', 'model' => 'STR-002', 'price' => 2190, 'material' => 'Хлопок с эластаном', 'sleeve' => 'Длинный рукав', 'fit' => 'Slim Fit', 'care' => 'Деликатная стирка'],
        ['name' => 'Рубашка в клетку', 'model' => 'CHK-003', 'price' => 2790, 'material' => '100% хлопок', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 30°C'],
        ['name' => 'Белая офисная рубашка', 'model' => 'WHT-004', 'price' => 1990, 'material' => 'Хлопок поплин', 'sleeve' => 'Длинный рукав', 'fit' => 'Classic Fit', 'care' => 'Отбеливание разрешено'],
        ['name' => 'Голубая рубашка Casual', 'model' => 'BLU-005', 'price' => 2290, 'material' => 'Хлопок с полиэстером', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Рубашка с коротким рукавом', 'model' => 'SHT-006', 'price' => 1890, 'material' => '100% хлопок', 'sleeve' => 'Короткий рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Льняная рубашка летняя', 'model' => 'LIN-007', 'price' => 3490, 'material' => '55% лён, 45% хлопок', 'sleeve' => 'Короткий рукав', 'fit' => 'Relaxed Fit', 'care' => 'Ручная или деликатная стирка'],
        ['name' => 'Рубашка Denim', 'model' => 'DNM-008', 'price' => 2990, 'material' => '100% хлопок деним', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Стирать вывернутой'],
        ['name' => 'Рубашка с воротником Button-down', 'model' => 'BTD-009', 'price' => 2590, 'material' => 'Хлопок Oxford', 'sleeve' => 'Длинный рукав', 'fit' => 'Slim Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Рубашка Flannel тёплая', 'model' => 'FLN-010', 'price' => 3190, 'material' => '100% хлопок фланель', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 30°C'],
        ['name' => 'Рубашка с манжетами French cuff', 'model' => 'FRN-011', 'price' => 4290, 'material' => 'Египетский хлопок', 'sleeve' => 'Длинный рукав', 'fit' => 'Tailored Fit', 'care' => 'Химчистка или деликатная стирка'],
        ['name' => 'Рубашка oversize', 'model' => 'OVR-012', 'price' => 2690, 'material' => 'Хлопок с вискозой', 'sleeve' => 'Длинный рукав', 'fit' => 'Oversize', 'care' => 'Машинная стирка 30°C'],
        ['name' => 'Рубашка с принтом геометрия', 'model' => 'GEO-013', 'price' => 2390, 'material' => '100% хлопок', 'sleeve' => 'Короткий рукав', 'fit' => 'Regular Fit', 'care' => 'Стирать наизнанку'],
        ['name' => 'Рубашка stretch комфорт', 'model' => 'STH-014', 'price' => 2790, 'material' => 'Хлопок 97%, эластан 3%', 'sleeve' => 'Длинный рукав', 'fit' => 'Slim Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Рубашка с нагрудным карманом', 'model' => 'PKT-015', 'price' => 2090, 'material' => 'Хлопок поплин', 'sleeve' => 'Короткий рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Рубашка Business Premium', 'model' => 'BIZ-016', 'price' => 4990, 'material' => '100% хлопок twill', 'sleeve' => 'Длинный рукав', 'fit' => 'Tailored Fit', 'care' => 'Профессиональная глажка'],
        ['name' => 'Рубашка с контрастной отделкой', 'model' => 'CTR-017', 'price' => 2890, 'material' => 'Хлопок с полиэстером', 'sleeve' => 'Длинный рукав', 'fit' => 'Slim Fit', 'care' => 'Машинная стирка 30°C'],
        ['name' => 'Рубашка Weekend Chambray', 'model' => 'CHM-018', 'price' => 2590, 'material' => '100% хлопок chambray', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Машинная стирка 40°C'],
        ['name' => 'Рубашка с вышивкой', 'model' => 'EMB-019', 'price' => 3690, 'material' => 'Хлопок Oxford', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Деликатная стирка'],
        ['name' => 'Рубашка Travel no-iron', 'model' => 'TRV-020', 'price' => 3290, 'material' => 'Хлопок с полиэстером', 'sleeve' => 'Длинный рукав', 'fit' => 'Regular Fit', 'care' => 'Не требует глажки'],
      ];

      foreach ($shirts as $index => $shirt) {
        $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $slug = 'demo-rubashka-'.$num.'-'.Str::slug($shirt['model']);

        $product = Product::create([
          'model' => $shirt['model'],
          'sku' => 'DEMO-SHIRT-'.$num,
          'quantity' => random_int(5, 50),
          'image' => self::IMAGE_PATH,
          'manufacturer_id' => $this->catalogManufacturerId($index),
          'price' => $shirt['price'],
          'sort_order' => $index + 1,
          'status' => true,
          'shipping' => true,
          'subtract' => true,
          'minimum' => 1,
        ]);

        $product->categories()->sync([$category->id]);

        ProductDescription::create([
          'product_id' => $product->id,
          'language_id' => $defaultLang->id,
          'name' => $shirt['name'],
          'slug' => $slug,
          'description' => '<p>Демо-рубашка для тестирования каталога. Модель '.$shirt['model'].'.</p>',
          'tag' => 'рубашка, демо, одежда',
          'meta_title' => $shirt['name'],
          'meta_description' => 'Пробная рубашка — '.$shirt['name'],
        ]);

        $this->attachAttributes($product, $defaultLang, $attributes, $shirt);

        $sizeProductOption = ProductOption::create([
          'product_id' => $product->id,
          'option_id' => $sizeOption->id,
          'value' => '',
          'required' => true,
        ]);

        foreach ($sizeValues as $sort => $sizeValue) {
          ProductOptionValue::create([
            'product_option_id' => $sizeProductOption->id,
            'product_id' => $product->id,
            'option_id' => $sizeOption->id,
            'option_value_id' => $sizeValue->id,
            'quantity' => random_int(2, 15),
            'subtract' => true,
            'price' => 0,
            'price_prefix' => '+',
          ]);
        }

        $colorProductOption = ProductOption::create([
          'product_id' => $product->id,
          'option_id' => $colorOption->id,
          'value' => '',
          'required' => false,
        ]);

        foreach ($colorValues as $colorValue) {
          $extraPrice = match ($colorValue->sort_order) {
            2 => 150,
            3 => 200,
            default => 0,
          };

          ProductOptionValue::create([
            'product_option_id' => $colorProductOption->id,
            'product_id' => $product->id,
            'option_id' => $colorOption->id,
            'option_value_id' => $colorValue->id,
            'quantity' => random_int(1, 10),
            'subtract' => true,
            'price' => $extraPrice,
            'price_prefix' => '+',
          ]);
        }
      }
    });

    $this->command?->info('Создано 20 пробных рубашек (SKU: DEMO-SHIRT-01 … DEMO-SHIRT-20).');
    $this->command?->line('Фото: /storage/'.self::IMAGE_PATH);
    $this->command?->line('Индекс поиска: php artisan scout:import "App\Models\Product"');
  }

  private function ensureDemoImage(): void
  {
    $path = storage_path('app/public/'.self::IMAGE_PATH);
    if (is_file($path)) {
      return;
    }

    @mkdir(dirname($path), 0755, true);

    $url = 'https://picsum.photos/seed/shirt-demo/600/800.jpg';
    $data = @file_get_contents($url);
    if ($data) {
      file_put_contents($path, $data);
    }
  }

  private function ensureCategory(Language $lang): Category
  {
    $existing = CategoryDescription::query()
      ->where('language_id', $lang->id)
      ->where('slug', 'rubashki-demo')
      ->first();

    if ($existing) {
      return Category::findOrFail($existing->category_id);
    }

    $category = Category::create([
      'parent_id' => null,
      'image' => self::IMAGE_PATH,
      'top' => true,
      'column' => 1,
      'sort_order' => 1,
      'status' => true,
    ]);

    CategoryDescription::create([
      'category_id' => $category->id,
      'language_id' => $lang->id,
      'name' => 'Рубашки (демо)',
      'slug' => 'rubashki-demo',
      'description' => 'Пробная категория с демо-рубашками',
    ]);

    Category::rebuildPaths();

    return $category;
  }

  /** @return array<string, Attribute> */
  private function ensureAttributes(Language $lang): array
  {
    $group = AttributeGroup::query()
      ->whereHas('descriptions', fn ($q) => $q->where('language_id', $lang->id)->where('name', 'Характеристики рубашки'))
      ->first();

    if (! $group) {
      $group = AttributeGroup::create(['sort_order' => 1]);
      AttributeGroupDescription::create([
        'attribute_group_id' => $group->id,
        'language_id' => $lang->id,
        'name' => 'Характеристики рубашки',
      ]);
    }

    $defs = [
      'material' => 'Материал',
      'sleeve' => 'Рукав',
      'fit' => 'Покрой',
      'care' => 'Уход',
    ];

    $result = [];
    foreach ($defs as $key => $label) {
      $result[$key] = $this->firstOrCreateAttributeByName(
        $group,
        $lang,
        $label,
        null,
        null,
        count($result) + 1
      );
    }

    return $result;
  }

  /** @return array{0: Option, 1: array<int, OptionValue>} */
  private function ensureSizeOption(Language $lang): array
  {
    $option = Option::query()
      ->where('type', 'select')
      ->whereHas('descriptions', fn ($q) => $q->where('language_id', $lang->id)->where('name', 'Размер'))
      ->first();

    if (! $option) {
      $option = Option::create(['type' => 'select', 'sort_order' => 1]);
      OptionDescription::create([
        'option_id' => $option->id,
        'language_id' => $lang->id,
        'name' => 'Размер',
      ]);
    }

    $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
    $values = [];

    foreach ($sizes as $i => $size) {
      $value = OptionValue::query()
        ->where('option_id', $option->id)
        ->whereHas('descriptions', fn ($q) => $q->where('language_id', $lang->id)->where('name', $size))
        ->first();

      if (! $value) {
        $value = OptionValue::create([
          'option_id' => $option->id,
          'image' => '',
          'sort_order' => $i + 1,
        ]);
        OptionValueDescription::create([
          'option_value_id' => $value->id,
          'language_id' => $lang->id,
          'option_id' => $option->id,
          'name' => $size,
        ]);
      }

      $values[] = $value;
    }

    return [$option, $values];
  }

  /** @return array{0: Option, 1: array<int, OptionValue>} */
  private function ensureColorOption(Language $lang): array
  {
    $option = Option::query()
      ->where('type', 'radio')
      ->whereHas('descriptions', fn ($q) => $q->where('language_id', $lang->id)->where('name', 'Цвет'))
      ->first();

    if (! $option) {
      $option = Option::create(['type' => 'radio', 'sort_order' => 2]);
      OptionDescription::create([
        'option_id' => $option->id,
        'language_id' => $lang->id,
        'name' => 'Цвет',
      ]);
    }

    $colors = ['Белый', 'Голубой', 'Синий'];
    $values = [];

    foreach ($colors as $i => $color) {
      $value = OptionValue::query()
        ->where('option_id', $option->id)
        ->whereHas('descriptions', fn ($q) => $q->where('language_id', $lang->id)->where('name', $color))
        ->first();

      if (! $value) {
        $value = OptionValue::create([
          'option_id' => $option->id,
          'image' => '',
          'sort_order' => $i + 1,
        ]);
        OptionValueDescription::create([
          'option_value_id' => $value->id,
          'language_id' => $lang->id,
          'option_id' => $option->id,
          'name' => $color,
        ]);
      }

      $values[] = $value;
    }

    return [$option, $values];
  }

  /** @param  array<string, Attribute>  $attributes */
  private function attachAttributes(Product $product, Language $lang, array $attributes, array $shirt): void
  {
    $map = [
      'material' => $shirt['material'],
      'sleeve' => $shirt['sleeve'],
      'fit' => $shirt['fit'],
      'care' => $shirt['care'],
    ];

    foreach ($map as $key => $text) {
      ProductAttribute::create([
        'product_id' => $product->id,
        'attribute_id' => $attributes[$key]->id,
        'language_id' => $lang->id,
        'text' => $text,
      ]);
    }
  }
}
