<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\RoutineCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutineCheck>
 */
class RoutineCheckFactory extends Factory
{
    /**
     * Define the model's default state: a routine ticked today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),  // creates a routine too, unless one is given
            'done_on' => today(),
        ];
    }
}
