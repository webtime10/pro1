<?php

namespace App\Http\Controllers\Admin;

use App\Http\Concerns\SyncsOpenCartDescriptions;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeDescription;
use App\Models\AttributeGroup;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttributeController extends Controller
{
    use SyncsOpenCartDescriptions;

    public function index(Request $request)
    {
        $pageTitle = 'Атрибуты';
        $defaultLanguage = Language::getDefault();
        $attributeGroupId = $request->query('attribute_group_id');

        $attributes = Attribute::query()
            ->with([
                'descriptions' => fn ($q) => $q->where('language_id', $defaultLanguage?->id),
                'attributeGroup.descriptions' => fn ($q) => $q->where('language_id', $defaultLanguage?->id),
            ])
            ->when($attributeGroupId, fn ($q) => $q->where('attribute_group_id', $attributeGroupId))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $attributeGroups = AttributeGroup::query()
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $defaultLanguage?->id)])
            ->orderBy('sort_order')
            ->get();

        return view('admin.attributes.index', compact('attributes', 'attributeGroups', 'pageTitle', 'defaultLanguage', 'attributeGroupId'));
    }

    public function create()
    {
        $pageTitle = 'Атрибут — создание';
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();
        $attributeGroups = $this->attributeGroupsForSelect($defaultLanguage);

        return view('admin.attributes.create', compact('pageTitle', 'languages', 'attributeGroups'));
    }

    public function store(Request $request)
    {
        $languages = Language::forAdminForms();
        $rules = [
            'attribute_group_id' => 'required|exists:attribute_groups,id',
            'sort_order' => 'nullable|integer|min:0',
        ];
        foreach ($languages as $language) {
            $rules['name_'.$language->code] = $language->is_default ? 'required|string|max:64' : 'nullable|string|max:64';
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages) {
            $attribute = Attribute::create([
                'attribute_group_id' => $request->attribute_group_id,
                'sort_order' => (int) $request->input('sort_order', 0),
            ]);
            $this->syncOpenCartDescriptions($attribute, AttributeDescription::class, 'attribute_id', $request, $languages);
        });

        return redirect()->route('admin.attributes.index')->with('success', 'Атрибут создан');
    }

    public function edit(string $id)
    {
        $pageTitle = 'Атрибут — редактирование';
        $attribute = Attribute::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();
        $attributeGroups = $this->attributeGroupsForSelect($defaultLanguage);

        return view('admin.attributes.edit', compact('attribute', 'pageTitle', 'languages', 'attributeGroups'));
    }

    public function update(Request $request, string $id)
    {
        $attribute = Attribute::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();
        $rules = [
            'attribute_group_id' => 'required|exists:attribute_groups,id',
            'sort_order' => 'nullable|integer|min:0',
        ];
        foreach ($languages as $language) {
            $rules['name_'.$language->code] = $language->is_default ? 'required|string|max:64' : 'nullable|string|max:64';
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages, $attribute) {
            $attribute->update([
                'attribute_group_id' => $request->attribute_group_id,
                'sort_order' => (int) $request->input('sort_order', 0),
            ]);
            $this->syncOpenCartDescriptions($attribute, AttributeDescription::class, 'attribute_id', $request, $languages);
        });

        return redirect()->route('admin.attributes.index')->with('success', 'Атрибут обновлён');
    }

    public function destroy(string $id)
    {
        Attribute::findOrFail($id)->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Атрибут удалён');
    }

    private function attributeGroupsForSelect(?Language $lang)
    {
        return AttributeGroup::query()
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $lang?->id)])
            ->orderBy('sort_order')
            ->get();
    }
}
