<?php

namespace App\Nova\Metrics;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

/**
 * Headline total of one model, e.g. "Speakers: 105".
 */
class ModelCount extends Value
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(private string $model, private string $label)
    {
        parent::__construct();
    }

    public function name(): string
    {
        return $this->label;
    }

    public function calculate(NovaRequest $request): ValueResult
    {
        return $this->result($this->model::count())->allowZeroResult();
    }
}
