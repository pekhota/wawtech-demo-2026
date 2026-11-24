<?php

namespace App\Nova;

use App\Nova\Cards\SortableCard;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class Faq extends Resource
{
    /**
     * @var class-string<\App\Models\Faq>
     */
    public static $model = \App\Models\Faq::class;

    /**
     * @var string
     */
    public static $title = 'question';

    /**
     * @var array<int, string>
     */
    public static $search = ['question', 'answer'];

    /**
     * @var string
     */
    public static $clickAction = 'preview';

    public static function label(): string
    {
        return 'FAQ';
    }

    public static function singularLabel(): string
    {
        return 'FAQ';
    }

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Question')
                ->sortable()
                ->showOnPreview()
                ->rules('required', 'max:255'),

            Trix::make('Answer')
                ->alwaysShow()
                ->showOnPreview()
                ->rules('required'),

            // Set by dragging on the "Reorder FAQs" card; new FAQs go to the end (see App\Models\Faq).
            Number::make('Sort Order')
                ->sortable()
                ->exceptOnForms(),
        ];
    }

    /**
     * @return array<int, Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            SortableCard::make(
                list: 'faqs',
                title: 'Reorder FAQs',
                hint: 'Drag a question, or focus one and use the arrow keys. Changes save straight away.',
                items: \App\Models\Faq::orderBy('sort_order')->orderBy('id')->get()
                    ->map(fn (\App\Models\Faq $faq) => ['id' => $faq->id, 'label' => $faq->question]),
            ),
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
