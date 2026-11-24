<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * For models ordered by drag and drop: a record created without a position goes to the end.
 */
trait AppendsToSortOrder
{
    protected static function bootAppendsToSortOrder(): void
    {
        static::creating(function (Model $model) {
            $model->sort_order ??= (static::max('sort_order') ?? -1) + 1;
        });
    }
}
