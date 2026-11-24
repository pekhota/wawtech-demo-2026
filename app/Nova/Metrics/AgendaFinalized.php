<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Progress;
use Laravel\Nova\Metrics\ProgressResult;

/**
 * Agenda slots that have a real title, out of all slots.
 */
class AgendaFinalized extends Progress
{
    public function calculate(NovaRequest $request): ProgressResult
    {
        $total = AgendaItem::count();

        return $this->result($total - AgendaItem::withPlaceholderTitle()->count(), $total)
            ->suffix('sessions announced')
            ->withoutSuffixInflection();
    }
}
