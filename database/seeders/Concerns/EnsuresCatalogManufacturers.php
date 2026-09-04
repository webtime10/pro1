<?php

namespace Database\Seeders\Concerns;

use App\Models\Manufacturer;

trait EnsuresCatalogManufacturers
{
    /** @var list<int>|null */
    private static ?array $catalogManufacturerIds = null;

    /** @return list<string> */
    protected function catalogManufacturerNames(): array
    {
        return [
            'Nike',
            'Adidas',
            'Puma',
            'Reebok',
            'Levi\'s',
            'Wrangler',
            'Uniqlo',
            'Zara',
            'H&M',
            'Lacoste',
            'Columbia',
            'The North Face',
        ];
    }

    /**
     * @return list<int>
     */
    protected function ensureCatalogManufacturers(): array
    {
        if (self::$catalogManufacturerIds !== null) {
            return self::$catalogManufacturerIds;
        }

        $ids = [];
        foreach ($this->catalogManufacturerNames() as $i => $name) {
            $row = Manufacturer::query()->firstOrCreate(
                ['name' => $name],
                ['sort_order' => $i + 1]
            );
            $ids[] = (int) $row->id;
        }

        return self::$catalogManufacturerIds = $ids;
    }

    protected function catalogManufacturerId(int $index): int
    {
        $ids = $this->ensureCatalogManufacturers();

        return $ids[abs($index) % count($ids)];
    }
}
