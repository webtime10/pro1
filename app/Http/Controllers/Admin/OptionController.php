<?php

namespace App\Http\Controllers\Admin;

use App\Http\Concerns\SyncsOpenCartDescriptions;
use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Option;
use App\Models\OptionDescription;
use App\Models\OptionValue;
use App\Models\OptionValueDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OptionController extends Controller
{
    use SyncsOpenCartDescriptions;

    public function index()
    {
        $pageTitle = 'Опции';
        $defaultLanguage = Language::getDefault();
        $options = Option::query()
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $defaultLanguage?->id)])
            ->withCount('values')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.options.index', compact('options', 'pageTitle', 'defaultLanguage'));
    }

    public function create()
    {
        $pageTitle = 'Опция — создание';
        $languages = Language::forAdminForms();

        return view('admin.options.create', compact('pageTitle', 'languages'));
    }

    public function store(Request $request)
    {
        $languages = Language::forAdminForms();
        $rules = [
            'type' => ['required', 'string', Rule::in(Option::TYPES)],
            'sort_order' => 'nullable|integer|min:0',
            'option_value.*.color' => 'nullable|string|max:7',
        ];
        foreach ($languages as $language) {
            $rules['name_'.$language->code] = $language->is_default ? 'required|string|max:128' : 'nullable|string|max:128';
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages) {
            $option = Option::create([
                'type' => $request->type,
                'sort_order' => (int) $request->input('sort_order', 0),
            ]);
            $this->syncOpenCartDescriptions($option, OptionDescription::class, 'option_id', $request, $languages);
            $this->syncOptionValues($option, $request, $languages);
        });

        return redirect()->route('admin.options.index')->with('success', 'Опция создана');
    }

    public function edit(string $id)
    {
        $pageTitle = 'Опция — редактирование';
        $option = Option::with(['descriptions', 'values.descriptions'])->findOrFail($id);
        $languages = Language::forAdminForms();

        return view('admin.options.edit', compact('option', 'pageTitle', 'languages'));
    }

    public function update(Request $request, string $id)
    {
        $option = Option::with(['descriptions', 'values.descriptions'])->findOrFail($id);
        $languages = Language::forAdminForms();
        $rules = [
            'type' => ['required', 'string', Rule::in(Option::TYPES)],
            'sort_order' => 'nullable|integer|min:0',
            'option_value.*.color' => 'nullable|string|max:7',
        ];
        foreach ($languages as $language) {
            $rules['name_'.$language->code] = $language->is_default ? 'required|string|max:128' : 'nullable|string|max:128';
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages, $option) {
            $option->update([
                'type' => $request->type,
                'sort_order' => (int) $request->input('sort_order', 0),
            ]);
            $this->syncOpenCartDescriptions($option, OptionDescription::class, 'option_id', $request, $languages);
            $this->syncOptionValues($option, $request, $languages);
        });

        return redirect()->route('admin.options.index')->with('success', 'Опция обновлена');
    }

    public function destroy(string $id)
    {
        Option::findOrFail($id)->delete();

        return redirect()->route('admin.options.index')->with('success', 'Опция удалена');
    }

    private function syncOptionValues(Option $option, Request $request, $languages): void
    {
        if (! in_array($option->type, ['select', 'radio', 'checkbox'], true)) {
            OptionValue::query()->where('option_id', $option->id)->delete();

            return;
        }

        $rows = $request->input('option_value', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        $keepIds = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $value = null;
            if (! empty($row['id'])) {
                $value = OptionValue::query()
                    ->where('option_id', $option->id)
                    ->where('id', $row['id'])
                    ->first();
            }

            if (! $value) {
                $value = new OptionValue(['option_id' => $option->id]);
            }

            $value->fill([
                'image' => (string) ($row['image'] ?? ''),
                'color' => $this->normalizeOptionColor((string) ($row['color'] ?? '')),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ]);
            $value->save();
            $keepIds[] = $value->id;

            foreach ($languages as $language) {
                $suffix = $language->code;
                $name = trim((string) ($row['name_'.$suffix] ?? ''));

                if (! $language->is_default && $name === '') {
                    OptionValueDescription::query()
                        ->where('option_value_id', $value->id)
                        ->where('language_id', $language->id)
                        ->delete();

                    continue;
                }

                if ($name === '' && ! $language->is_default) {
                    continue;
                }

                OptionValueDescription::updateOrCreate(
                    [
                        'option_value_id' => $value->id,
                        'language_id' => $language->id,
                    ],
                    [
                        'option_id' => $option->id,
                        'name' => $name,
                    ]
                );
            }
        }

        OptionValue::query()
            ->where('option_id', $option->id)
            ->whereNotIn('id', $keepIds ?: [0])
            ->delete();
    }

    private function normalizeOptionColor(string $color): string
    {
        $color = trim($color);

        if ($color === '' || ! preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $m)) {
            return '';
        }

        $hex = strtolower($m[1]);
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.$hex;
    }
}
