<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Language;
use App\Models\Manufacturer;
use App\Models\ProductAttribute;
use App\Models\ProductDescription;
use App\Models\ProductOption;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    protected $fillable = [
        'model',
        'sku',
        'upc',
        'ean',
        'jan',
        'isbn',
        'mpn',
        'location',
        'quantity',
        'stock_status_id',
        'image',
        'manufacturer_id',
        'shipping',
        'price',
        'points',
        'tax_class_id',
        'date_available',
        'weight',
        'weight_class_id',
        'length',
        'width',
        'height',
        'length_class_id',
        'subtract',
        'minimum',
        'sort_order',
        'status',
        'viewed',
    ];

    protected $casts = [
        'shipping' => 'boolean',
        'subtract' => 'boolean',
        'status' => 'boolean',
        'date_available' => 'date',
        'price' => 'decimal:4',
        'weight' => 'decimal:8',
        'length' => 'decimal:8',
        'width' => 'decimal:8',
        'height' => 'decimal:8',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(ProductDescription::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Карточки листинга: только поля плитки, в порядке $ids.
     *
     * @param  list<int>  $ids
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function catalogCardsByIds(array $ids, int $languageId)
    {
        if ($ids === []) {
            return collect();
        }

        $items = static::query()
            ->select(['id', 'image', 'price', 'manufacturer_id'])
            ->whereIn('id', $ids)
            ->with([
                'descriptions' => fn ($q) => $q->where('language_id', $languageId)
                    ->select(['id', 'product_id', 'language_id', 'name', 'slug']),
                'manufacturer:id,name',
            ])
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $items->get($id))->filter()->values();
    }

    /**
     * Данные для индекса Meilisearch (витрина: поиск и фильтры).
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['descriptions', 'categories']);

        $defaultLanguage = Language::getDefault();
        $description = $defaultLanguage
            ? $this->descriptions->firstWhere('language_id', $defaultLanguage->id)
            : $this->descriptions->first();

        return [
            'id' => $this->id,
            'name' => $description->name ?? $this->model,
            'slug' => $description->slug ?? null,
            'sku' => $this->sku,
            'model' => $this->model,
            'price' => (float) $this->price,
            'sort_order' => (int) $this->sort_order,
            'status' => (bool) $this->status,
            'category_ids' => $this->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->status;
    }
}
