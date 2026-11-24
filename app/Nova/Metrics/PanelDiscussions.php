<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use App\Models\Speaker;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;
use Laravel\Nova\Nova;

class PanelDiscussions extends Table
{
    /**
     * @return array<int, MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        $timezone = Nova::resolveUserTimezone($request) ?? config('app.timezone');

        return AgendaItem::with('stage', 'tags', 'speakers')->orderBy('starts_at')->get()
            // Only panels with an announced line-up.
            ->filter(fn (AgendaItem $item) => $item->format === 'Panel' && $item->speakers->isNotEmpty())
            ->map(fn (AgendaItem $item) => MetricTableRow::make()
                ->icon('user-group')
                ->iconClass('wt-text-main')
                ->title($item->title)
                ->subtitle($this->summary($item, $timezone))
                ->actions(fn () => [MenuItem::link('Open', "/resources/agenda-items/{$item->id}")]))
            ->values()
            ->all();
    }

    /**
     * E.g. "Moderated by Jane Doe · 3 panelists · MAIN · Thu 11:00".
     */
    private function summary(AgendaItem $item, string $timezone): string
    {
        [$moderators, $panelists] = $item->speakers->partition(fn (Speaker $speaker) => (bool) $speaker->pivot->is_moderator);

        return collect([
            $moderators->isNotEmpty() ? 'Moderated by '.$moderators->pluck('name')->implode(' & ') : null,
            $panelists->count().' '.Str::plural('panelist', $panelists->count()),
            $item->stage?->name,
            $item->starts_at->timezone($timezone)->format('D H:i'),
        ])->filter()->implode(' · ');
    }
}
