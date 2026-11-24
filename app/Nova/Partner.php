<?php

namespace App\Nova;

use App\Enums\PartnerTier;
use App\Nova\Repeaters\Contact;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Repeater;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class Partner extends Resource
{
    /**
     * @var class-string<\App\Models\Partner>
     */
    public static $model = \App\Models\Partner::class;

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

            Image::make('Logo')
                ->disk('public')
                ->path('partners')
                ->indexWidth(96)
                ->detailWidth(240)
                ->prunable()
                ->rules('nullable', 'image', 'max:2048'),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            // Colours match the Partners per Tier chart; classes live in resources/css/nova-theme.css.
            Badge::make('Tier', fn () => $this->tier->value)
                ->label(fn (string $tier) => PartnerTier::from($tier)->label())
                ->types(collect(PartnerTier::cases())->mapWithKeys(fn (PartnerTier $tier) => [$tier->value => "wt-badge-{$tier->value}"])->all())
                ->icons([
                    PartnerTier::Title->value => 'star',
                    PartnerTier::Gold->value => 'trophy',
                    PartnerTier::Silver->value => 'shield-check',
                    PartnerTier::Event->value => 'calendar',
                    PartnerTier::Community->value => 'heart',
                ]),

            Select::make('Tier')
                ->options(PartnerTier::class)
                ->displayUsingLabels()
                ->onlyOnForms()
                ->rules('required', Rule::enum(PartnerTier::class)),

            URL::make('Website', 'url')
                ->rules('nullable', 'url', 'max:255'),

            Number::make('Sort Order')
                ->sortable()
                ->min(0)
                ->default(0)
                ->rules('required', 'integer', 'min:0'),

            Repeater::make('Contacts')
                ->repeatables([Contact::make()])
                ->asJson(),
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
