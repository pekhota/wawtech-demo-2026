<?php

namespace App\Nova\Filters;

use App\Models\AgendaItem;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

/**
 * Filters sessions to one conference day, in the editor's (Warsaw) timezone.
 */
class ConferenceDay extends Filter
{
    /**
     * @var string
     */
    public $component = 'select-filter';

    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        $day = Carbon::parse($value, $this->timezone($request));

        return $query->whereBetween('starts_at', [$day->copy()->startOfDay()->utc(), $day->copy()->endOfDay()->utc()]);
    }

    /**
     * @return array<string, string>
     */
    public function options(NovaRequest $request): array
    {
        $timezone = $this->timezone($request);

        return AgendaItem::orderBy('starts_at')->pluck('starts_at')
            ->map(fn (Carbon $startsAt) => $startsAt->timezone($timezone))
            ->unique(fn (Carbon $day) => $day->toDateString())
            ->mapWithKeys(fn (Carbon $day) => [$day->format('l, j M') => $day->toDateString()])
            ->all();
    }

    private function timezone(NovaRequest $request): string
    {
        return Nova::resolveUserTimezone($request) ?? config('app.timezone');
    }
}
