<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

/**
 * Total length of all sessions, rounded to whole hours.
 */
class HoursOfContent extends Value
{
    public function name(): string
    {
        return 'Hours of Content';
    }

    public function calculate(NovaRequest $request): ValueResult
    {
        $minutes = AgendaItem::get(['starts_at', 'ends_at'])
            ->sum(fn (AgendaItem $item) => $item->starts_at->diffInMinutes($item->ends_at));

        return $this->result((int) round($minutes / 60))->suffix('hours')->withoutSuffixInflection();
    }
}
