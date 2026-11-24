<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use App\Models\Speaker;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;
use Laravel\Nova\Nova;

/**
 * Content gaps an editor should close before the conference.
 */
class NeedsAttention extends Table
{
    private string $timezone;

    /**
     * @return array<int, MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        $this->timezone = Nova::resolveUserTimezone($request) ?? config('app.timezone');

        $placeholders = AgendaItem::withPlaceholderTitle()->with('stage', 'speakers')->orderBy('starts_at')->get()
            ->map(fn (AgendaItem $item) => $this->row(
                $item->speakers->pluck('name')->whenEmpty(fn () => collect([$item->title]))->implode(', '),
                "Title not announced · {$this->slot($item)}",
                "/resources/agenda-items/{$item->id}/edit",
            ));

        $withoutStage = AgendaItem::whereNull('stage_id')->orderBy('starts_at')->get()
            ->map(fn (AgendaItem $item) => $this->row(
                $item->title,
                "No stage assigned · {$this->slot($item)}",
                "/resources/agenda-items/{$item->id}/edit",
            ));

        $unscheduled = Speaker::doesntHave('agendaItems')->orderBy('sort_order')->get()
            ->map(fn (Speaker $speaker) => $this->row(
                $speaker->name,
                'Announced speaker without a session',
                "/resources/speakers/{$speaker->id}",
            ));

        return [...$withoutStage, ...$unscheduled, ...$placeholders];
    }

    private function row(string $title, string $subtitle, string $path): MetricTableRow
    {
        return MetricTableRow::make()
            ->icon('exclamation-circle')
            ->iconClass('text-yellow-500')
            ->title($title)
            ->subtitle($subtitle)
            ->actions(fn () => [MenuItem::link('Open', $path)]);
    }

    private function slot(AgendaItem $item): string
    {
        $startsAt = $item->starts_at->timezone($this->timezone);

        return collect([$item->stage?->name, $startsAt->format('D j M, H:i')])->filter()->implode(' · ');
    }
}
