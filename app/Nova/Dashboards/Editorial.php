<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\AgendaFinalized;
use App\Nova\Metrics\NeedsAttention;
use App\Nova\Metrics\SpeakersScheduled;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboard;

/**
 * What editors still have to fill in before the conference.
 */
class Editorial extends Dashboard
{
    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return [
            AgendaFinalized::make()->width('1/2'),
            SpeakersScheduled::make()->width('1/2'),
            NeedsAttention::make()->width('full'),
        ];
    }

    public function uriKey(): string
    {
        return 'editorial';
    }
}
