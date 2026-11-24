<?php

namespace App\Models;

use App\Models\Concerns\AppendsToSortOrder;
use Database\Factories\SpeakerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Nova\Actions\Actionable;

#[Fillable(['name', 'slug', 'position', 'company', 'photo', 'profile_url', 'bio', 'is_featured', 'sort_order'])]
class Speaker extends Model
{
    /** @use HasFactory<SpeakerFactory> */
    use Actionable, AppendsToSortOrder, HasFactory;

    /**
     * @return BelongsToMany<AgendaItem, $this>
     */
    public function agendaItems(): BelongsToMany
    {
        return $this->belongsToMany(AgendaItem::class)->withPivot('is_moderator', 'sort_order');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }
}
