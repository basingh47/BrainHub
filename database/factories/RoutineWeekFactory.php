<?php

namespace Database\Factories;

use App\Models\RoutineWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutineWeek>
 */
class RoutineWeekFactory extends Factory
{
    /**
     * Define the model's default state: a past week's sheet.
     *
     * week_start is unique, so each sheet gets a different Monday.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'week_start' => today()->startOfWeek()->subWeeks(fake()->unique()->numberBetween(1, 200)),
        ];
    }

    /**
     * State: this week's sheet (starts on this Monday).
     */
    public function current(): static
    {
        return $this->state(fn () => [
            'week_start' => today()->startOfWeek(),
        ]);
    }
}
