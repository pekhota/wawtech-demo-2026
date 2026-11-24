<?php

namespace App\Nova\Actions;

use App\Models\AgendaItem;
use App\Models\Stage;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class MoveToStage extends Action
{
    /**
     * @param  Collection<int, AgendaItem>  $models
     */
    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $stage = Stage::findOrFail($fields->get('stage'));

        $models->each->update(['stage_id' => $stage->id]);

        return Action::message("Moved {$models->count()} session(s) to {$stage->name}.");
    }

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Select::make('Stage')
                ->options(fn () => Stage::orderBy('sort_order')->pluck('name', 'id'))
                ->rules('required', 'exists:stages,id'),
        ];
    }
}
