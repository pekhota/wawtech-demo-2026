<?php

namespace App\Models;

use App\Enums\PartnerTier;
use App\Policies\ContentPolicy;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'tier', 'url', 'logo', 'contacts', 'sort_order'])]
#[UsePolicy(ContentPolicy::class)]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => PartnerTier::class,
            'contacts' => 'array',
        ];
    }
}
