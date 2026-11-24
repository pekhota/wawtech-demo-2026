<?php

namespace App\Nova\Metrics;

use App\Models\Speaker;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;

/**
 * Companies sending more than one speaker, most speakers first.
 */
class TopCompanies extends Table
{
    private const int SHOWN = 8;

    public function name(): string
    {
        return 'Most Represented Companies';
    }

    /**
     * @return array<int, MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        return Speaker::where('company', '!=', '')->orderBy('name')->get()
            ->groupBy('company')
            ->filter(fn (Collection $speakers) => $speakers->count() > 1)
            // Sorting is stable, so companies with the same count stay alphabetical.
            ->sortKeys()
            ->sortByDesc(fn (Collection $speakers) => $speakers->count())
            ->take(self::SHOWN)
            // A company name made only of digits comes back as an int array key.
            ->map(fn (Collection $speakers, int|string $company) => MetricTableRow::make()
                ->icon('building-office-2')
                ->iconClass('wt-text-engineering')
                ->title((string) $company)
                ->subtitle("{$speakers->count()} speakers: {$speakers->pluck('name')->implode(', ')}")
                ->actions(fn () => [MenuItem::link('View speakers', '/resources/speakers?speakers_search='.rawurlencode((string) $company))]))
            ->values()
            ->all();
    }
}
