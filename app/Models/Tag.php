<?php

namespace App\Models;

use App\Policies\ContentPolicy;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
#[UsePolicy(ContentPolicy::class)]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<AgendaItem, $this>
     */
    public function agendaItems(): BelongsToMany
    {
        return $this->belongsToMany(AgendaItem::class);
    }
}
