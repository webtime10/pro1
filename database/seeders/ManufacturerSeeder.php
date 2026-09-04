<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use App\Support\CatalogCache;
use Database\Seeders\Concerns\EnsuresCatalogManufacturers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Бренды + привязка к товарам без производителя.
 *
 * php artisan db:seed --class=ManufacturerSeeder
 */
class ManufacturerSeeder extends Seeder
{
    use EnsuresCatalogManufacturers;

    public function run(): void
    {
        $ids = $this->ensureCatalogManufacturers();
        $n = count($ids);
        $list = implode(',', array_map('intval', $ids));

        DB::update("
            UPDATE products
            SET manufacturer_id = ELT(1 + MOD(id, {$n}), {$list})
            WHERE manufacturer_id IS NULL
        ");

        CatalogCache::forgetFilter();
        foreach (DB::table('category_product')->distinct()->pluck('category_id') as $categoryId) {
            CatalogCache::forget('wtfilter.manufacturers.'.$categoryId);
            CatalogCache::forget('wtfilter.counters.'.$categoryId);
            CatalogCache::forget('wtfilter.total.'.$categoryId);
            CatalogCache::forget('wtfilter.prices.'.$categoryId);
        }

        $this->command?->info('Производителей: '.Manufacturer::query()->count());
        $this->command?->info('Товаров с брендом: '.DB::table('products')->whereNotNull('manufacturer_id')->count());
    }
}
