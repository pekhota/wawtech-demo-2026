<?php

namespace App\Nova\Metrics;

use App\Models\Speaker;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

/**
 * Speakers grouped by seniority, read from their job title.
 */
class SpeakerRoles extends Partition
{
    /**
     * @var array<string, string>
     */
    private const array COLORS = [
        'Founders & C-level' => '#F501A4',
        'VPs, directors & heads' => '#9859FF',
        'Engineers & scientists' => '#3C60FF',
        'Product, people & more' => '#02FEB9',
    ];

    public function name(): string
    {
        return "Who's Speaking";
    }

    public function calculate(NovaRequest $request): PartitionResult
    {
        $roles = Speaker::pluck('position')->countBy(fn (?string $position) => self::role($position ?? ''));

        return $this->result(collect(self::COLORS)->map(fn (string $color, string $role) => $roles[$role] ?? 0)->filter()->all())
            ->colors(self::COLORS);
    }

    /**
     * ponytail: keyword match on free-text job titles, checked most senior first; add a role column if editors need to correct one.
     */
    private static function role(string $position): string
    {
        return match (true) {
            (bool) preg_match('/\b(CEO|CTO|CMO|COO|CPO|CFO|Chief|Founder|Founding|Co-owner)\b/i', $position) => 'Founders & C-level',
            (bool) preg_match('/\b(VP|Director|Head|Board|Leader)\b/i', $position) => 'VPs, directors & heads',
            (bool) preg_match('/Engineer|Developer|Architect|Scientist|Roboticist|\bDev\b/i', $position) => 'Engineers & scientists',
            default => 'Product, people & more',
        };
    }
}
