<?php

namespace Database\Factories;

use App\Models\AgendaItem;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaItem>
 */
class AgendaItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stage_id' => Stage::factory(),
            'title' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'starts_at' => $startsAt = fake()->dateTimeBetween('2026-11-26 09:00', '2026-11-27 17:00'),
            'ends_at' => (clone $startsAt)->modify('+25 minutes'),
        ];
    }
}
