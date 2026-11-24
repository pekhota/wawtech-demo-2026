<?php

namespace App\Http\Controllers\Nova;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Saves the order set on a drag-and-drop card (App\Nova\Cards\SortableCard).
 */
class ReorderController extends Controller
{
    public function __invoke(ReorderRequest $request): Response
    {
        $model = $request->model();

        DB::transaction(function () use ($request, $model) {
            foreach ($request->validated('ids') as $position => $id) {
                $model::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return response()->noContent();
    }
}
