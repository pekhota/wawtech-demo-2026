<?php

namespace App\Models;

use App\Models\Concerns\AppendsToSortOrder;
use App\Policies\ContentPolicy;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question', 'answer', 'sort_order'])]
#[UsePolicy(ContentPolicy::class)]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use AppendsToSortOrder, HasFactory;
}
