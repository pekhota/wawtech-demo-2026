<?php

namespace App\Nova\Repeaters;

use Laravel\Nova\Fields\Email;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Repeater\Repeatable;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * A person to contact at a partner company.
 */
class Contact extends Repeatable
{
    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Text::make('Name')->rules('required', 'max:255'),
            Email::make('Email')->rules('required', 'email', 'max:255'),
            Text::make('Role')->rules('nullable', 'max:255'),
        ];
    }
}
