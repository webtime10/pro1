<?php

namespace Database\Seeders\Concerns;

use App\Models\Attribute;
use App\Models\AttributeDescription;
use App\Models\AttributeGroup;
use App\Models\Language;

trait EnsuresCatalogAttributes
{
    /**
     * Один attribute_id на название (без учёта регистра), независимо от группы.
     */
    protected function firstOrCreateAttributeByName(
        AttributeGroup $group,
        Language $primary,
        string $primaryName,
        ?Language $secondary = null,
        ?string $secondaryName = null,
        int $sortOrder = 0
    ): Attribute {
        $primaryName = trim($primaryName);
        $normalized = mb_strtolower($primaryName);

        $attr = Attribute::query()
            ->whereHas('descriptions', function ($q) use ($normalized) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);
            })
            ->orderBy('id')
            ->first();

        if ($attr) {
            AttributeDescription::query()->firstOrCreate(
                [
                    'attribute_id' => $attr->id,
                    'language_id' => $primary->id,
                ],
                ['name' => $primaryName]
            );

            if ($secondary && $secondaryName) {
                AttributeDescription::query()->firstOrCreate(
                    [
                        'attribute_id' => $attr->id,
                        'language_id' => $secondary->id,
                    ],
                    ['name' => trim($secondaryName)]
                );
            }

            return $attr;
        }

        $attr = Attribute::create([
            'attribute_group_id' => $group->id,
            'sort_order' => $sortOrder,
        ]);

        AttributeDescription::create([
            'attribute_id' => $attr->id,
            'language_id' => $primary->id,
            'name' => $primaryName,
        ]);

        if ($secondary && $secondaryName) {
            AttributeDescription::create([
                'attribute_id' => $attr->id,
                'language_id' => $secondary->id,
                'name' => trim($secondaryName),
            ]);
        }

        return $attr;
    }
}
