<?php

namespace App\Nova\Dashboards;

use App\Models\AgendaItem;
use App\Models\Stage;
use App\Nova\Metrics\HoursOfContent;
use App\Nova\Metrics\ModelCount;
use App\Nova\Metrics\PanelDiscussions;
use App\Nova\Metrics\SessionLengths;
use App\Nova\Metrics\SessionsPerHour;
use App\Nova\Metrics\Topics;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboard;

/**
 * The shape of the programme: how much, when, about what, and who's on the panels.
 */
class AgendaOverview extends Dashboard
{
    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return [
            ModelCount::make(AgendaItem::class, 'Sessions')->icon('calendar'),
            HoursOfContent::make()->icon('clock'),
            ModelCount::make(Stage::class, 'Stages')->icon('building-library'),
            SessionsPerHour::make()->width('full'),
            Topics::make()->width('1/2')->dynamicHeight(),
            SessionLengths::make()->width('1/2')->dynamicHeight(),
            PanelDiscussions::make()->width('full'),
        ];
    }

    public function uriKey(): string
    {
        return 'agenda-overview';
    }
}
