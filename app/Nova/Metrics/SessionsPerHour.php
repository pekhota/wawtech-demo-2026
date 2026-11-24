<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Laravel\Nova\Nova;

/**
 * Sessions starting in each hour of each conference day, in the editor's timezone.
 */
class SessionsPerHour extends Trend
{
    public function name(): string
    {
        return 'Sessions per Hour';
    }

    /**
     * One range per conference day, keyed by date (Y-m-d).
     *
     * @return array<string, string>
     */
    public function ranges(): array
    {
        return ['all' => 'Both days'] + $this->startHoursByDay(request())
            ->map(fn (Collection $hours) => $hours->first()->format('l, j M'))
            ->all();
    }

    public function calculate(NovaRequest $request): TrendResult
    {
        $days = $this->startHoursByDay($request);

        if (($request->range ?? 'all') !== 'all') {
            $days = $days->only($request->range);
        }

        $trend = $days
            // Every hour between a day's first and last session, so quiet hours show as 0 instead of vanishing.
            ->flatMap(fn (Collection $hours) => collect(CarbonPeriod::create($hours->first(), '1 hour', $hours->last()))
                ->mapWithKeys(fn (CarbonInterface $hour) => [$hour->format('D H:i') => $hours->filter->eq($hour)->count()]));

        return (new TrendResult)->trend($trend->all())->showSumValue()->suffix('sessions');
    }

    /**
     * @return Collection<string, Collection<int, CarbonInterface>>
     */
    private function startHoursByDay(Request $request): Collection
    {
        $timezone = Nova::resolveUserTimezone($request) ?? config('app.timezone');

        return AgendaItem::orderBy('starts_at')->pluck('starts_at')
            ->map(fn (CarbonInterface $startsAt) => $startsAt->timezone($timezone)->startOfHour())
            ->groupBy(fn (CarbonInterface $hour) => $hour->toDateString());
    }
}
