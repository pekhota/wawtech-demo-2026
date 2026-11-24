<?php

namespace App\Nova;

use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Tag extends Resource
{
    /**
     * @var class-string<\App\Models\Tag>
     */
    public static $model = \App\Models\Tag::class;

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
                ->rules('required', 'max:255')
                ->creationRules('unique:tags,name')
                ->updateRules('unique:tags,name,{{resourceId}}'),

            BelongsToMany::make('Agenda Items', 'agendaItems', AgendaItem::class),
        ];
    }
}
