<?php

namespace Database\Factories;

use App\Models\DailyTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyTask>
 */
class DailyTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),                                  // "Fix the login bug."
            'description' => fake()->optional()->sentence(),                 // sometimes null
            'category' => fake()->randomElement(['office', 'learning', 'personal']),
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'due_date' => today(),
            'due_time' => fake()->optional()->time('H:i'),                  // "14:30" or null
            'status' => fake()->randomElement(['pending', 'in_progress']),
            'completed_at' => null,
        ];
    }

    /**
     * State: due in the past and not completed → overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => today()->subDays(fake()->numberBetween(1, 5)),
        ]);
    }

    /**
     * State: already completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
