<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'English', 'code' => 'en', 'sort_order' => 1],
            ['name' => 'Yoruba', 'code' => 'yo', 'sort_order' => 2],
            ['name' => 'Igbo', 'code' => 'ig', 'sort_order' => 3],
            ['name' => 'Hausa', 'code' => 'ha', 'sort_order' => 4],
            ['name' => 'French', 'code' => 'fr', 'sort_order' => 5],
        ];

        foreach ($languages as $language) {
            Language::query()->updateOrCreate(
                ['code' => $language['code']],
                [...$language, 'is_active' => true]
            );
        }
    }
}
