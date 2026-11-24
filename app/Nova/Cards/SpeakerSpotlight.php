<?php

namespace App\Nova\Cards;

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Nova\Speaker as SpeakerResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Card;
use Laravel\Nova\Nova;

/**
 * The homepage's featured speakers with their photos, in homepage order.
 * The Vue component is plain JavaScript in resources/js/nova/speaker-spotlight-card.js, so there is no build step.
 */
class SpeakerSpotlight extends Card
{
    /**
     * @var string
     */
    public $component = 'speaker-spotlight-card';

    /**
     * @var string
     */
    public $width = self::FULL_WIDTH;

    /**
     * @var string
     */
    public $height = self::DYNAMIC_HEIGHT;

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $timezone = Nova::resolveUserTimezone(request()) ?? config('app.timezone');

        $speakers = Speaker::where('is_featured', true)
            ->with(['agendaItems' => fn (Builder $query) => $query->with('stage')->orderBy('starts_at')])
            ->orderBy('sort_order')
            ->get();

        return array_merge(parent::jsonSerialize(), [
            'totalSpeakers' => Speaker::count(),
            'speakers' => $speakers->map(fn (Speaker $speaker) => [
                'id' => $speaker->id,
                'name' => $speaker->name,
                'role' => (new SpeakerResource($speaker))->subtitle(),
                'photo' => $speaker->photo ? Storage::disk('public')->url($speaker->photo) : null,
                'path' => "/resources/speakers/{$speaker->id}",
                'session' => $this->firstSession($speaker->agendaItems->first(), $timezone),
            ])->all(),
        ]);
    }

    /**
     * @return array{stage: string, color: string, time: string}|null
     */
    private function firstSession(?AgendaItem $session, string $timezone): ?array
    {
        if ($session === null) {
            return null;
        }

        return [
            'stage' => $session->stage->name ?? 'No stage',
            'color' => $session->stage->color ?? '#94A3B8',
            'time' => $session->starts_at->timezone($timezone)->format('D H:i'),
        ];
    }
}
