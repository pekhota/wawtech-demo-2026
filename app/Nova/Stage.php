<?php

namespace App\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Fields\Color;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Stage extends Resource
{
    /**
     * @var class-string<\App\Models\Stage>
     */
    public static $model = \App\Models\Stage::class;

    /**
     * @var string
     */
    public static $title = 'name';

    /**
     * @var array<int, string>
     */
    public static $search = ['name'];

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->showWhenPeeking()
                ->rules('required', 'max:255')
                ->creationRules('unique:stages,name')
                ->updateRules('unique:stages,name,{{resourceId}}'),

            Color::make('Color')
                ->showWhenPeeking()
                ->rules('nullable', 'regex:/^#[0-9a-fA-F]{6}$/'),

            Number::make('Sort Order')
                ->sortable()
                ->min(0)
                ->default(0)
                ->rules('required', 'integer', 'min:0'),

            HasMany::make('Agenda Items', 'agendaItems', AgendaItem::class),
        ];
    }

    /**
     * @return Builder
     */
    public static function defaultOrderings($query)
    {
        return $query->orderBy('sort_order');
    }
}
