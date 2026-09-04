<?php

namespace App\Http\Controllers\Admin;

use App\Http\Concerns\SyncsOpenCartDescriptions;
use App\Http\Controllers\Controller;
use App\Models\AttributeGroup;
use App\Models\AttributeGroupDescription;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttributeGroupController extends Controller
{
    use SyncsOpenCartDescriptions;

    public function index()
    {
        $pageTitle = 'Группы атрибутов';
        $defaultLanguage = Language::getDefault();
        $attributeGroups = AttributeGroup::query()
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $defaultLanguage?->id)])
            ->withCount('attributes')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.attribute-groups.index', compact('attributeGroups', 'pageTitle', 'defaultLanguage'));
    }

    public function create()
    {
        $pageTitle = 'Группа атрибутов — создание';
        $languages = Language::forAdminForms();

        return view('admin.attribute-groups.create', compact('pageTitle', 'languages'));
    }

    public function store(Request $request)
    {
        $languages = Language::forAdminForms();
        $rules = ['sort_order' => 'nullable|integer|min:0'];
        foreach ($languages as $language) {
            $rules['name_'.$language->code] = $language->is_default ? 'required|string|max:64' : 'nullable|string|max:64';
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages) {
            $group = AttributeGroup::create([
                'sort_order' => (int) $request->input('sort_order', 0),
            ]);
            $this->syncOpenCartDescriptions($group, AttributeGroupDescription::class, 'attribute_group_id', $request, $languages);
        });

        return redirect()->route('admin.attribute-groups.index')->with('success', 'Группа атрибутов создана');
    }

    public function edit(string $id)
    {
        $pageTitle = 'Группа атрибутов — редактирование';
        $attributeGroup = AttributeGroup::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();

        return view('admin.attribute-groups.edit', compact('attributeGroup', 'pageTitle', 'languages'));
    }

    public function update(Request $request, string $id)
    {
        $attributeGroup = AttributeGroup::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();
        $rules = ['sort_order' => 'nullable|integer|min:0'];
        foreach ($languages as $language) {
            $rules['name_'.$language->code] = $language->is_default ? 'required|string|max:64' : 'nullable|string|max:64';
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages, $attributeGroup) {
            $attributeGroup->update([
                'sort_order' => (int) $request->input('sort_order', 0),
            ]);
            $this->syncOpenCartDescriptions($attributeGroup, AttributeGroupDescription::class, 'attribute_group_id', $request, $languages);
        });

        return redirect()->route('admin.attribute-groups.index')->with('success', 'Группа атрибутов обновлена');
    }

    public function destroy(string $id)
    {
        AttributeGroup::findOrFail($id)->delete();

        return redirect()->route('admin.attribute-groups.index')->with('success', 'Группа атрибутов удалена');
    }
}
