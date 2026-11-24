<?php

namespace App\Nova\Metrics;

use App\Models\Speaker;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

/**
 * Distinct companies the speakers work for.
 */
class Companies extends Value
{
    public function calculate(NovaRequest $request): ValueResult
    {
        return $this->result(Speaker::where('company', '!=', '')->distinct()->count('company'));
    }
}
