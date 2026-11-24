<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class SessionLengths extends Partition
{
    /**
     * Upper bound in minutes => label, shortest first.
     *
     * @var array<int, string>
     */
    private const array BUCKETS = [
        30 => 'Up to 30 min',
        50 => '31–50 min',
        90 => '51–90 min',
        PHP_INT_MAX => 'Over 90 min',
    ];

    /**
     * @var array<string, string>
     */
    private const array COLORS = [
        'Up to 30 min' => '#02FEB9',
        '31–50 min' => '#3C60FF',
        '51–90 min' => '#9859FF',
        'Over 90 min' => '#F501A4',
    ];

    public function calculate(NovaRequest $request): PartitionResult
    {
        $lengths = AgendaItem::get(['starts_at', 'ends_at'])
            ->countBy(fn (AgendaItem $item) => $this->bucket((int) $item->starts_at->diffInMinutes($item->ends_at)));

        // Keep the buckets in length order rather than by count.
        $ordered = collect(self::BUCKETS)->values()->mapWithKeys(fn (string $label) => [$label => $lengths[$label] ?? 0])->filter();

        return $this->result($ordered->all())->colors(self::COLORS);
    }

    private function bucket(int $minutes): string
    {
        return collect(self::BUCKETS)->first(fn (string $label, int $upTo) => $minutes <= $upTo);
    }
}
