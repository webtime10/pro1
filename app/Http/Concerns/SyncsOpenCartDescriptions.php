<?php

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait SyncsOpenCartDescriptions
{
    /**
     * @param  class-string<Model>  $descriptionClass
     * @param  array<int, string>  $fields
     */
    protected function syncOpenCartDescriptions(
        Model $parent,
        string $descriptionClass,
        string $foreignKey,
        Request $request,
        Collection $languages,
        array $fields = ['name'],
    ): void {
        foreach ($languages as $language) {
            $suffix = $language->code;
            $payload = ['language_id' => $language->id];
            $hasContent = false;

            foreach ($fields as $field) {
                $value = $request->input($field.'_'.$suffix);
                if ($field === 'name' && is_string($value)) {
                    $value = trim($value);
                }
                $payload[$field] = $value ?? '';
                if ($value !== null && $value !== '') {
                    $hasContent = true;
                }
            }

            if (! $language->is_default && ! $hasContent) {
                $descriptionClass::query()
                    ->where($foreignKey, $parent->getKey())
                    ->where('language_id', $language->id)
                    ->delete();

                continue;
            }

            if (! $hasContent && ! $language->is_default) {
                continue;
            }

            $descriptionClass::updateOrCreate(
                [
                    $foreignKey => $parent->getKey(),
                    'language_id' => $language->id,
                ],
                $payload
            );
        }
    }
}
