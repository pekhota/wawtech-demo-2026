<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Seeds the agenda extracted from https://wawtech.io/2026/agenda.
 *
 * Times on the site are Warsaw wall-clock times; they are stored in UTC.
 * Must run after SpeakerSeeder, since speakers are matched by name.
 */
class AgendaItemSeeder extends Seeder
{
    /**
     * Stage names and colours, in the order the site lists them.
     *
     * @var array<string, string>
     */
    private const array STAGES = [
        'MAIN' => '#9859FF',
        'ENGINEERING' => '#3C60FF',
        'GROWTH' => '#02FEB9',
        'STUDIO' => '#CCFF00',
        'PRO STAGE' => '#F501A4',
    ];

    public function run(): void
    {
        $stageIds = collect(array_keys(self::STAGES))
            ->mapWithKeys(fn (string $name, int $index) => [
                $name => Stage::create(['name' => $name, 'color' => self::STAGES[$name], 'sort_order' => $index])->id,
            ]);

        $speakerIds = Speaker::pluck('id', 'name');

        foreach (File::json(database_path('seeders/data/agenda.json')) as $item) {
            $agendaItem = AgendaItem::create([
                'stage_id' => $item['stage'] ? $stageIds[$item['stage']] : null,
                'title' => $item['title'],
                'description' => $item['description'],
                'starts_at' => Carbon::parse($item['starts_at'], 'Europe/Warsaw')->utc(),
                'ends_at' => Carbon::parse($item['ends_at'], 'Europe/Warsaw')->utc(),
                // The site marks Polish sessions with a " – PL" title suffix.
                'language' => str_ends_with($item['title'], '– PL') ? 'pl' : 'en',
            ]);

            $agendaItem->tags()->attach(
                collect($item['tags'])->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id)
            );

            $agendaItem->speakers()->attach(
                collect($item['speakers'])->mapWithKeys(fn (array $speaker, int $index) => [
                    $speakerIds[$speaker['name']] => ['is_moderator' => $speaker['is_moderator'], 'sort_order' => $index],
                ])
            );
        }
    }
}
