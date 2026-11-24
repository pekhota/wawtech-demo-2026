<?php

namespace App\Models;

use App\Policies\ContentPolicy;
use Database\Factories\StageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'sort_order'])]
#[UsePolicy(ContentPolicy::class)]
class Stage extends Model
{
    /** @use HasFactory<StageFactory> */
    use HasFactory;

    /**
     * @return HasMany<AgendaItem, $this>
     */
    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }
}
