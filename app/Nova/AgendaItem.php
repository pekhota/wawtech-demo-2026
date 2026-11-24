<?php

namespace App\Nova;

use App\Nova\Actions\MoveToStage;
use App\Nova\Filters\ConferenceDay;
use App\Nova\Metrics\AgendaItemsPerStage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Line;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Tag;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;

class AgendaItem extends Resource
{
    /**
     * @var class-string<\App\Models\AgendaItem>
     */
    public static $model = \App\Models\AgendaItem::class;

    /**
     * @var string
     */
    public static $title = 'title';

    /**
     * @var array<int, string>
     */
    public static $search = ['title'];

    /**
     * @var array<int, string>
     */
    public static $with = ['stage', 'tags', 'speakers'];

    /**
     * @var string
     */
    public static $tableStyle = 'tight';

    /**
     * @var bool
     */
    public static $showColumnBorders = true;

    /**
     * @var array<string, string>
     */
    private const array LANGUAGES = ['en' => 'English', 'pl' => 'Polish', 'uk' => 'Ukrainian'];

    /**
     * @return array<int, Field|Panel>
     */
    public function fields(NovaRequest $request): array
    {
        $timezone = Nova::resolveUserTimezone($request) ?? config('app.timezone');

        return [
            ID::make()->sortable(),

            Stack::make('Session', [
                Line::make('Title', fn () => Str::limit($this->title, 60))->asHeading(),
                Line::make('Speakers', fn () => $this->speakers->pluck('name')->implode(', '))->asSmall(),
            ])->onlyOnIndex(),

            Stack::make('Slot', [
                Line::make('Stage', fn () => sprintf(
                    '<span class="wt-stage" style="--stage: %s">%s</span>',
                    e($this->stage->color ?? '#94A3B8'),
                    e($this->stage->name ?? 'No stage'),
                ))->asHtml(),
                Line::make('Time', fn () => $this->starts_at->timezone($timezone)->format('D H:i').'–'.$this->ends_at->timezone($timezone)->format('H:i'))
                    ->asSmall(),
            ])->onlyOnIndex(),

            Text::make('Title')
                ->hideFromIndex()
                ->rules('required', 'max:255'),

            // Colours match the Session Formats chart; classes live in resources/css/nova-theme.css.
            Badge::make('Format', fn () => $this->format)
                ->types([
                    'Talk' => 'wt-badge-talk',
                    'Panel' => 'wt-badge-panel',
                    'Workshop' => 'wt-badge-workshop',
                    'Podcast' => 'wt-badge-podcast',
                    'AMA' => 'wt-badge-ama',
                    'Special' => 'wt-badge-special',
                ])
                ->icons([
                    'Talk' => 'microphone',
                    'Panel' => 'user-group',
                    'Workshop' => 'wrench-screwdriver',
                    'Podcast' => 'radio',
                    'AMA' => 'chat-bubble-left-right',
                    'Special' => 'sparkles',
                ]),

            Panel::make('Schedule', [
                BelongsTo::make('Stage')
                    ->nullable()
                    ->hideFromIndex()
                    ->filterable()
                    ->showCreateRelationButton(),

                DateTime::make('Starts At')
                    ->hideFromIndex()
                    ->filterable()
                    ->rules('required', 'date'),

                DateTime::make('Ends At')
                    ->hideFromIndex()
                    ->help('Filled in as 25 minutes after the start; adjust for longer sessions.')
                    ->rules('required', 'date', 'after:starts_at')
                    ->dependsOnCreating(['starts_at'], function (DateTime $field, NovaRequest $request, FormData $formData) {
                        if ($formData->starts_at) {
                            $field->setValue(Carbon::parse($formData->starts_at)->addMinutes(25));
                        }
                    }),

                Text::make('Duration', fn () => (int) $this->starts_at->diffInMinutes($this->ends_at).' min')
                    ->onlyOnDetail(),

                Select::make('Language')
                    ->options(self::LANGUAGES)
                    ->displayUsingLabels()
                    ->hideFromIndex()
                    ->filterable()
                    ->default('en')
                    ->rules('required', 'in:'.implode(',', array_keys(self::LANGUAGES))),
            ]),

            Panel::make('Content', [
                Tag::make('Tags')
                    ->preload()
                    ->showCreateRelationButton()
                    ->hideFromIndex(),

                Trix::make('Description')
                    ->alwaysShow(),

                File::make('Slides')
                    ->disk('public')
                    ->path('slides')
                    ->acceptedTypes('.pdf')
                    ->prunable()
                    ->hideFromIndex()
                    ->rules('nullable', 'mimes:pdf', 'max:20480'),
            ]),

            BelongsToMany::make('Speakers')
                ->fields(fn () => [
                    Boolean::make('Moderator', 'is_moderator'),
                    Number::make('Sort Order')->default(0)->rules('required', 'integer', 'min:0'),
                ]),
        ];
    }

    /**
     * @return array<int, Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            AgendaItemsPerStage::make()->refreshWhenFiltersChange()->dynamicHeight(),
        ];
    }

    /**
     * @return array<int, Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [
            ConferenceDay::make(),
        ];
    }

    /**
     * @return array<int, MoveToStage>
     */
    public function actions(NovaRequest $request): array
    {
        // ContentPolicy already blocks them; don't show read-only accounts disabled buttons.
        if ($request->user()?->is_read_only) {
            return [];
        }

        return [
            MoveToStage::make(),
        ];
    }

    /**
     * @return Builder
     */
    public static function defaultOrderings($query)
    {
        return $query->orderBy('starts_at');
    }
}
