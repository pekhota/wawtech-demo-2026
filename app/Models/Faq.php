<?php

namespace App\Models;

use App\Models\Concerns\AppendsToSortOrder;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question', 'answer', 'sort_order'])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use AppendsToSortOrder, HasFactory;
}
