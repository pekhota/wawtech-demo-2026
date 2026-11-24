<?php

namespace App\Nova\Cards;

use App\Http\Requests\ReorderRequest;
use Laravel\Nova\Card;

/**
 * Drag-and-drop ordering for a list of records, as a text list or a photo grid.
 * The Vue component is plain JavaScript in resources/js/nova/sortable-card.js, so there is no build step.
 */
class SortableCard extends Card
{
    /**
     * @var string
     */
    public $component = 'sortable-card';

    /**
     * @var string
     */
    public $width = self::FULL_WIDTH;

    /**
     * @var string
     */
    public $height = self::DYNAMIC_HEIGHT;

    /**
     * @param  key-of<ReorderRequest::LISTS>  $list
     * @param  iterable<int, array{id: int, label: string, image?: string|null, highlight?: bool}>  $items  In their current order.
     * @param  'list'|'grid'  $layout
     */
    public function __construct(string $list, string $title, string $hint, iterable $items, string $layout = 'list')
    {
        parent::__construct();

        $this->withMeta([
            'title' => $title,
            'hint' => $hint,
            'layout' => $layout,
            'items' => collect($items)->values()->all(),
            'saveUrl' => route('nova.reorder', $list, absolute: false),
        ]);
    }
}
