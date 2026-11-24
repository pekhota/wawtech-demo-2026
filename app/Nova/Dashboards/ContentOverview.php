<?php

namespace App\Nova\Dashboards;

use App\Models\Faq;
use App\Models\Partner;
use App\Models\Speaker;
use App\Nova\Metrics\Companies;
use App\Nova\Metrics\FeaturedSpeakers;
use App\Nova\Metrics\ModelCount;
use App\Nova\Metrics\PartnersPerTier;
use App\Nova\Metrics\SpeakerRoles;
use App\Nova\Metrics\TopCompanies;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboard;

/**
 * Who is speaking and who is backing the event.
 */
class ContentOverview extends Dashboard
{
    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return [
            ModelCount::make(Speaker::class, 'Speakers')->icon('microphone')->width('1/4'),
            Companies::make()->icon('building-office-2')->width('1/4'),
            ModelCount::make(Partner::class, 'Partners')->icon('briefcase')->width('1/4'),
            ModelCount::make(Faq::class, 'FAQs')->icon('question-mark-circle')->width('1/4'),
            FeaturedSpeakers::make()->width('1/2'),
            TopCompanies::make()->width('1/2'),
            SpeakerRoles::make()->width('1/2')->dynamicHeight(),
            PartnersPerTier::make()->width('1/2')->dynamicHeight(),
        ];
    }

    public function uriKey(): string
    {
        return 'content-overview';
    }
}
