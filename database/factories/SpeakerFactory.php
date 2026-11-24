<?php

namespace Database\Factories;

use App\Models\Speaker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Speaker>
 */
class SpeakerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->name(),
            'slug' => Str::slug($name),
            'position' => fake()->jobTitle(),
            'company' => fake()->company(),
            'profile_url' => fake()->url(),
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
