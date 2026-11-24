<?php

namespace App\Nova\Lenses;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;

/**
 * Speakers who appear in more than one session, busiest first.
 */
class BusiestSpeakers extends Lens
{
    public static function query(LensRequest $request, Builder $query): Builder|Paginator
    {
        return $request->withOrdering(
            $request->withFilters($query->withCount('agendaItems')->has('agendaItems', '>', 1)),
            fn (Builder $query) => $query->orderByDesc('agenda_items_count')->orderBy('name'),
        );
    }

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Avatar::make('Photo')->disk('public'),
            Text::make('Name')->sortable(),
            Text::make('Company'),
            Number::make('Sessions', 'agenda_items_count')->sortable(),
        ];
    }
}
