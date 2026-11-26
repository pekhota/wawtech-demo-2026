<?php

namespace App\Http\Requests;

use App\Models\Faq;
use App\Models\Speaker;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A full new order from a drag-and-drop card (App\Nova\Cards\SortableCard): every id exactly once, first to last.
 */
class ReorderRequest extends FormRequest
{
    /**
     * Lists that can be reordered: route key => model with a sort_order column.
     *
     * @var array<string, class-string<Model>>
     */
    public const array LISTS = [
        'faqs' => Faq::class,
        'speakers' => Speaker::class,
    ];

    /**
     * The route's nova:api middleware already limits this to Nova users and
     * turns read-only accounts (the public demo) away.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $model = $this->model();

        return [
            // Every record must be listed, so no two end up sharing a position.
            'ids' => ['required', 'array', 'size:'.$model::count()],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists($model, 'id')],
        ];
    }

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return self::LISTS[$this->route('list')];
    }
}
