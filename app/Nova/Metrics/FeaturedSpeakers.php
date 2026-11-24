<?php

namespace App\Nova\Metrics;

use App\Models\Speaker;
use App\Nova\Speaker as SpeakerResource;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;

/**
 * Speakers shown in the homepage hero, in homepage order.
 */
class FeaturedSpeakers extends Table
{
    /**
     * @return array<int, MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        return Speaker::where('is_featured', true)->orderBy('sort_order')->get()
            ->map(fn (Speaker $speaker) => MetricTableRow::make()
                ->icon('star')
                ->iconClass('wt-text-pro')
                ->title($speaker->name)
                ->subtitle((new SpeakerResource($speaker))->subtitle() ?? '')
                ->actions(fn () => [MenuItem::link('Open', "/resources/speakers/{$speaker->id}")]))
            ->all();
    }
}
