<?php

namespace App\Nova\Metrics;

use App\Models\Speaker;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Progress;
use Laravel\Nova\Metrics\ProgressResult;

/**
 * Announced speakers who are on at least one agenda item.
 */
class SpeakersScheduled extends Progress
{
    public function calculate(NovaRequest $request): ProgressResult
    {
        return $this->result(Speaker::has('agendaItems')->count(), Speaker::count())
            ->suffix('speakers on the agenda')
            ->withoutSuffixInflection();
    }
}
