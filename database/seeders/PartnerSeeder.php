<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds partners extracted from https://wawtech.io/#partners.
 */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        foreach (File::json(database_path('seeders/data/partners.json')) as $index => $partner) {
            $logo = "partners/{$partner['logo']}";
            Storage::disk('public')->put($logo, File::get(database_path("seeders/data/images/{$logo}")));

            Partner::create([...$partner, 'logo' => $logo, 'sort_order' => $index]);
        }
    }
}
