<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class SessionFormats extends Partition
{
    /**
     * Matches the Format badge colours in resources/css/nova-theme.css.
     *
     * @var array<string, string>
     */
    private const array COLORS = [
        'Talk' => '#3C60FF',
        'Panel' => '#9859FF',
        'Workshop' => '#CCFF00',
        'Podcast' => '#02FEB9',
        'AMA' => '#F501A4',
        'Special' => '#94A3B8',
    ];

    public function calculate(NovaRequest $request): PartitionResult
    {
        // Format is derived in PHP (AgendaItem::format), so it can't be grouped in SQL.
        $formats = AgendaItem::with('tags', 'speakers')->get()->countBy('format')->sortDesc();

        return $this->result($formats->all())->colors(self::COLORS);
    }
}
