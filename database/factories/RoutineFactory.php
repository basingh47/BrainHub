<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Routine>
 */
class RoutineFactory extends Factory
{
    /**
     * Define the model's default state: an active top-level routine.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),  // Each routine belongs to a user
            'parent_id' => null,
            'name' => fake()->randomElement(['Wake up', 'Exercise', 'Read 20 min', 'Drink 2 L water', 'Meditate', 'Plan tomorrow']),
            'target_time' => fake()->optional()->time('H:i'),  // "06:30" or null
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * State: a sub-task of the given routine (e.g. "2 km run" under "Exercise").
     */
    public function subTaskOf(Routine $parent): static
    {
        return $this->state(fn () => [
            'user_id' => $parent->user_id,
            'parent_id' => $parent->id,
            'target_time' => null,
        ]);
    }

    /**
     * State: deactivated (hidden from new sheets, history kept).
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
