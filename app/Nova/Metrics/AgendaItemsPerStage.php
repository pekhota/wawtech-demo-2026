<?php

namespace App\Nova\Metrics;

use App\Models\AgendaItem;
use App\Models\Stage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class AgendaItemsPerStage extends Partition
{
    public function name(): string
    {
        return 'Sessions per Stage';
    }

    public function calculate(NovaRequest $request): PartitionResult
    {
        $stages = Stage::all(['id', 'name', 'color']);
        $stageNames = $stages->pluck('name', 'id');

        return $this->count($request, AgendaItem::class, 'stage_id')
            ->label(fn (?int $stageId) => $stageNames[$stageId] ?? 'No stage')
            // Sessions without a stage are grouped under an empty key.
            ->colors($stages->pluck('color', 'id')->all() + ['' => '#94A3B8']);
    }
}
