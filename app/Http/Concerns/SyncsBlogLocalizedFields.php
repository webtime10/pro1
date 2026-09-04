<?php

namespace App\Http\Concerns;

use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait SyncsBlogLocalizedFields
{
    /**
     * @param  Collection<int, Language>  $languages
     */
    protected function mergeLocalizedSlugsFromDefaultName(Request $request, $languages): void
    {
        $defaultLanguage = $languages->firstWhere('is_default', true) ?? $languages->first();
        if (! $defaultLanguage) {
            return;
        }

        foreach ($languages as $language) {
            $nameKey = 'name_'.$language->code;
            $request->merge([
                $nameKey => trim((string) $request->input($nameKey, '')),
            ]);
        }

        $baseSlug = Str::slug((string) $request->input('name_'.$defaultLanguage->code, ''));

        foreach ($languages as $language) {
            $key = 'slug_'.$language->code;
            $slugRaw = $request->input($key);

            if (blank($slugRaw)) {
                $request->merge([$key => $baseSlug !== '' ? $baseSlug : null]);
            } else {
                $normalized = Str::slug(trim((string) $slugRaw));
                $request->merge([$key => $normalized !== '' ? $normalized : null]);
            }
        }
    }

    /**
     * @param  Collection<int, Language>  $languages
     * @return array<string, mixed>
     */
    protected function descriptionRules(string $table, $languages, ?Model $owner = null, string $ownerKey = 'id'): array
    {
        $rules = [];

        foreach ($languages as $language) {
            $suffix = $language->code;

            $unique = Rule::unique($table, 'slug')
                ->where('language_id', $language->id);

            if ($owner) {
                $desc = $owner->descriptions->firstWhere('language_id', $language->id);
                $unique = $unique->ignore($desc?->id);
            }

            $rules['name_'.$suffix] = ['required', 'string', 'min:1', 'max:255'];
            $rules['slug_'.$suffix] = ['required', 'string', 'min:1', 'max:255', $unique];
            $rules['description_'.$suffix] = 'nullable|string';
            $rules['meta_title_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_h1_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_description_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_keyword_'.$suffix] = 'nullable|string|max:255';
            $rules['tag_'.$suffix] = 'nullable|string';
        }

        return $rules;
    }
}
