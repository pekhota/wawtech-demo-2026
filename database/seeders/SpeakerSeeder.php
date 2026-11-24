<?php

namespace Database\Seeders;

use App\Models\Speaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds speakers extracted from https://wawtech.io/2026/speakers.
 */
class SpeakerSeeder extends Seeder
{
    public function run(): void
    {
        foreach (File::json(database_path('seeders/data/speakers.json')) as $index => $speaker) {
            $photo = "speakers/{$speaker['photo']}";
            Storage::disk('public')->put($photo, File::get(database_path("seeders/data/images/{$photo}")));

            Speaker::create([...$speaker, 'slug' => Str::slug($speaker['name']), 'photo' => $photo, 'sort_order' => $index]);
        }
    }
}
