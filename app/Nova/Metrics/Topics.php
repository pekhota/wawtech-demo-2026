<?php

namespace App\Nova\Metrics;

use App\Models\Tag;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * The most used session tags, with the rest grouped as "Other".
 */
class Topics extends Partition
{
    /**
     * Tags that mark a session's format rather than its subject (see AgendaItem::format()).
     *
     * @var array<int, string>
     */
    private const array FORMAT_TAGS = ['panel', 'workshop', 'podcast'];

    private const int SHOWN = 6;

    /**
     * Stage colours, given out by rank.
     *
     * @var array<int, string>
     */
    private const array PALETTE = ['#F501A4', '#9859FF', '#3C60FF', '#02FEB9', '#CCFF00', '#F5B301'];

    public function calculate(NovaRequest $request): PartitionResult
    {
        $topics = Tag::withCount('agendaItems')->orderByDesc('agenda_items_count')->orderBy('name')->get()
            ->reject(fn (Tag $tag) => $tag->agenda_items_count === 0 || in_array(strtolower($tag->name), self::FORMAT_TAGS))
            ->mapWithKeys(fn (Tag $tag) => [ucfirst($tag->name) => $tag->agenda_items_count]);

        $result = $topics->take(self::SHOWN);
        $other = $topics->skip(self::SHOWN)->sum();

        if ($other > 0) {
            $result->put('Other', $other);
        }

        // "Other" is only ever 7th, past the palette, so it falls back to grey.
        return $this->result($result->all())
            ->colors($result->keys()->mapWithKeys(fn (string $label, int $rank) => [$label => self::PALETTE[$rank] ?? '#94A3B8'])->all());
    }
}
