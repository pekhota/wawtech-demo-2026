<?php

namespace App\Nova\Dashboards;

use App\Models\AgendaItem;
use App\Models\Partner;
use App\Models\Speaker;
use App\Nova\Cards\SpeakerSpotlight;
use App\Nova\Metrics\AgendaItemsPerStage;
use App\Nova\Metrics\ModelCount;
use App\Nova\Metrics\PartnersPerTier;
use App\Nova\Metrics\SessionFormats;
use App\Nova\Metrics\SessionsPerHour;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboards\Main as Dashboard;

/**
 * Conference at a glance.
 */
class Main extends Dashboard
{
    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return [
            ModelCount::make(Speaker::class, 'Speakers')->icon('microphone'),
            ModelCount::make(AgendaItem::class, 'Sessions')->icon('calendar'),
            ModelCount::make(Partner::class, 'Partners')->icon('briefcase'),
            SpeakerSpotlight::make(),
            SessionsPerHour::make()->width('full'),
            AgendaItemsPerStage::make()->dynamicHeight(),
            SessionFormats::make()->dynamicHeight(),
            PartnersPerTier::make()->dynamicHeight(),
        ];
    }
}
