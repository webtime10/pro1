<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Language;
use App\Support\CatalogCache;
use App\Support\CatalogLocale;
use App\Support\CatalogUrl;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // AdminLTE/Bootstrap admin UI — default Tailwind pagination looks broken.
        Paginator::useBootstrapFour();

        View::composer('catalog.layout', function ($view) {
            try {
                $lang = CatalogLocale::current();
            } catch (\Throwable) {
                $lang = Language::getDefault();
            }

            if (! $lang) {
                $view->with('menuCategories', collect());
                $view->with('catalogLang', null);
                $view->with('catalogLanguages', collect());

                return;
            }

            try {
                $menuCategories = CatalogCache::remember('catalog.menu_categories.'.$lang->id, function () use ($lang) {
                    return $this->buildMenuCategories($lang);
                }, 300);
            } catch (\Throwable) {
                $menuCategories = $this->buildMenuCategories($lang);
            }

            $view->with('menuCategories', $menuCategories);
            $view->with('catalogLang', $lang);
            try {
                $view->with('catalogLanguages', Language::getActive());
            } catch (\Throwable) {
                $view->with('catalogLanguages', collect([$lang]));
            }
        });
    }

    private function buildMenuCategories(Language $lang)
    {
        $all = Category::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with([
                'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
                'parent.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            ])
            ->get();

        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);

        $build = function ($parentKey) use (&$build, $byParent, $lang) {
            return ($byParent->get($parentKey) ?? collect())->map(function ($cat) use (&$build, $lang) {
                $d = $cat->descriptions->firstWhere('language_id', $lang->id);
                $slug = $d->slug ?? null;
                if (! $slug) {
                    return null;
                }

                return [
                    'id' => $cat->id,
                    'name' => $d->name ?? ('#'.$cat->id),
                    'slug' => $slug,
                    'href' => CatalogUrl::category($cat, $lang),
                    'children' => $build($cat->id),
                ];
            })->filter()->values();
        };

        return $build(0);
    }
}
