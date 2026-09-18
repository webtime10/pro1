<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'ru',
                'name' => 'Русский',
                'locale' => 'ru-RU',
                'directory' => 'ru-ru',
                'sort_order' => 1,
                'status' => true,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'locale' => 'en-GB',
                'directory' => 'en-gb',
                'sort_order' => 2,
                'status' => true,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'he',
                'name' => 'עברית',
                'locale' => 'he-IL',
                'directory' => 'he-il',
                'sort_order' => 3,
                'status' => true,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'ar',
                'name' => 'العربية',
                'locale' => 'ar-SA',
                'directory' => 'ar-sa',
                'sort_order' => 4,
                'status' => true,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'uk',
                'name' => 'Українська',
                'locale' => 'uk-UA',
                'directory' => 'uk-ua',
                'sort_order' => 5,
                'status' => true,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(
                ['code' => $lang['code']],
                $lang
            );
        }

        // Старый код ua → uk (ISO 639-1)
        Language::query()->where('code', 'ua')->delete();
    }
}
