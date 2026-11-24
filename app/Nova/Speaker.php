<?php

namespace App\Nova;

use App\Nova\Cards\SortableCard;
use App\Nova\Lenses\BusiestSpeakers;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Line;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Tabs\Tab;

class Speaker extends Resource
{
    /**
     * @var class-string<\App\Models\Speaker>
     */
    public static $model = \App\Models\Speaker::class;

    /**
     * @var string
     */
    public static $title = 'name';

    /**
     * @var array<int, string>
     */
    public static $search = ['name', 'position', 'company'];

    /**
     * @var array<int, int>
     */
    public static $perPageOptions = [25, 50, 100];

    /**
     * Shown under the name in search results and relationship pickers.
     */
    public function subtitle(): ?string
    {
        return collect([$this->position, $this->company])->filter()->implode(' @ ') ?: null;
    }

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Tab::group(fields: [
                Tab::make('Profile', [
                    Avatar::make('Photo')
                        ->disk('public')
                        ->path('speakers')
                        ->prunable()
                        ->showOnPreview()
                        ->rules('nullable', 'image', 'max:4096'),

                    Stack::make('Speaker', [
                        Line::make('Name')->asHeading(),
                        Line::make('Role', fn () => $this->subtitle())->asSmall(),
                    ])->onlyOnIndex(),

                    Text::make('Homepage', fn () => $this->is_featured ? '<span class="wt-pill wt-badge-featured">★ Featured</span>' : null)
                        ->asHtml()
                        ->onlyOnIndex(),

                    Text::make('Name')
                        ->hideFromIndex()
                        ->copyable()
                        ->showOnPreview()
                        ->rules('required', 'max:255'),

                    Slug::make('Slug')
                        ->from('Name')
                        ->hideFromIndex()
                        ->help('Used in the public speaker URL.')
                        ->rules('required', 'max:255')
                        ->creationRules('unique:speakers,slug')
                        ->updateRules('unique:speakers,slug,{{resourceId}}'),

                    Text::make('Position')
                        ->hideFromIndex()
                        ->showOnPreview()
                        ->rules('nullable', 'max:255'),

                    Text::make('Company')
                        ->hideFromIndex()
                        ->showOnPreview()
                        ->rules('nullable', 'max:255'),

                    URL::make('Profile URL', 'profile_url')
                        ->hideFromIndex()
                        ->showOnPreview()
                        ->displayUsing(fn (?string $url) => $url ? parse_url($url, PHP_URL_HOST) : null)
                        ->rules('nullable', 'url', 'max:255'),

                    Boolean::make('Featured', 'is_featured')
                        ->hideFromIndex()
                        ->filterable()
                        ->help('Featured speakers are shown in the homepage hero.'),

                    // Set by dragging on the "Reorder Speakers" card; new speakers go to the end.
                    Number::make('Sort Order')
                        ->sortable()
                        ->exceptOnForms(),

                    Markdown::make('Bio')
                        ->showOnPreview()
                        ->rules('nullable'),
                ]),

                Tab::make('Sessions', [
                    BelongsToMany::make('Agenda Items', 'agendaItems', AgendaItem::class)
                        ->fields(fn () => [
                            Boolean::make('Moderator', 'is_moderator'),
                            Number::make('Sort Order')->default(0)->rules('required', 'integer', 'min:0'),
                        ]),
                ]),
            ]),
        ];
    }

    /**
     * @return array<int, BusiestSpeakers>
     */
    /**
     * @return array<int, Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            SortableCard::make(
                list: 'speakers',
                title: 'Reorder Speakers',
                hint: 'Drag photos into homepage order, or focus one and use the arrow keys. Featured speakers have a pink ring.',
                items: \App\Models\Speaker::orderBy('sort_order')->orderBy('id')->get()
                    ->map(fn (\App\Models\Speaker $speaker) => [
                        'id' => $speaker->id,
                        'label' => $speaker->name,
                        'image' => $speaker->photo ? Storage::disk('public')->url($speaker->photo) : null,
                        'highlight' => $speaker->is_featured,
                    ]),
                layout: 'grid',
            ),
        ];
    }

    public function lenses(NovaRequest $request): array
    {
        return [
            BusiestSpeakers::make(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    public function actions(NovaRequest $request): array
    {
        // ContentPolicy already blocks them; don't show read-only accounts disabled buttons.
        if ($request->user()?->is_read_only) {
            return [];
        }

        return [
            Action::using('Feature on homepage', fn (ActionFields $fields, Collection $speakers) => $speakers->each->update(['is_featured' => true]))
                ->showInline(),

            Action::using('Remove from homepage', fn (ActionFields $fields, Collection $speakers) => $speakers->each->update(['is_featured' => false]))
                ->showInline(),

            ExportAsCsv::make()
                ->nameable()
                ->withFormat(fn (\App\Models\Speaker $speaker) => [
                    'Name' => $speaker->name,
                    'Position' => $speaker->position,
                    'Company' => $speaker->company,
                    'Profile' => $speaker->profile_url,
                    'Featured' => $speaker->is_featured ? 'yes' : 'no',
                ]),
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
