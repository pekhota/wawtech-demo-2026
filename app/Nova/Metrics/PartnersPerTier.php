<?php

namespace App\Nova\Metrics;

use App\Enums\PartnerTier;
use App\Models\Partner;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class PartnersPerTier extends Partition
{
    public function name(): string
    {
        return 'Partners per Tier';
    }

    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, Partner::class, 'tier')
            ->label(fn (string $tier) => PartnerTier::from($tier)->label())
            // Matches the Tier badge colours in resources/css/nova-theme.css.
            ->colors([
                PartnerTier::Title->value => '#F501A4',
                PartnerTier::Gold->value => '#F5B301',
                PartnerTier::Silver->value => '#94A3B8',
                PartnerTier::Event->value => '#3C60FF',
                PartnerTier::Community->value => '#02FEB9',
            ]);
    }
}
