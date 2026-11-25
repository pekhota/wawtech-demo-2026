<?php

namespace App\Models;

use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Nova\Actions\Actionable;

#[Fillable(['stage_id', 'title', 'description', 'starts_at', 'ends_at', 'language', 'slides'])]
class AgendaItem extends Model
{
    /** @use HasFactory<AgendaItemFactory> */
    use Actionable, HasFactory;

    /**
     * @return BelongsTo<Stage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /**
     * @return BelongsToMany<Speaker, $this>
     */
    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class)
            ->withPivot('is_moderator', 'sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Sessions the site still announces as "TBD" / "TBA" (e.g. "TBD", "Podcast TBD").
     */
    #[Scope]
    protected function withPlaceholderTitle(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->where('title', 'like', '%TBD')->orWhere('title', 'like', '%TBA'));
    }

    /**
     * Session format shown as a badge, derived from tags, title and speakers.
     *
     * ponytail: derived, not stored; add a `format` column if editors need to override it.
     *
     * @return Attribute<string, never>
     */
    protected function format(): Attribute
    {
        return Attribute::get(function (): string {
            $tags = $this->tags->map(fn (Tag $tag) => strtolower($tag->name));

            return match (true) {
                $tags->contains('panel') || $this->speakers->count() >= 3 => 'Panel',
                $tags->contains('workshop') => 'Workshop',
                $tags->contains('podcast') || str_starts_with($this->title, 'Podcast') => 'Podcast',
                str_starts_with($this->title, 'AMA') || str_starts_with($this->title, 'Ask Me Anything') => 'AMA',
                $this->speakers->isEmpty() => 'Special',
                default => 'Talk',
            };
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
