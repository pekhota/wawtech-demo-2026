<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds FAQ entries extracted from https://wawtech.io/2026/faq.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (File::json(database_path('seeders/data/faqs.json')) as $index => $faq) {
            Faq::create([...$faq, 'sort_order' => $index]);
        }
    }
}
