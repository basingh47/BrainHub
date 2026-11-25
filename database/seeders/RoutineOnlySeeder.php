<?php

namespace Database\Seeders;

use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local dummy data: fills ONLY the routines table, owned by the first account.
 * Adds rows (does not empty the table). Never run in production.
 *
 * php artisan db:seed --class=RoutineOnlySeeder
 */
class RoutineOnlySeeder extends Seeder
{
    public function run(): void
    {
        // Owner of all rows: the first account (you). Stops with a clear error if no user exists.
        $me = User::orderBy('id')->firstOrFail();

        // A parent with 3 sub-tasks (subTaskOf() gives them the same owner as the parent).
        $exercise = Routine::factory()->for($me)->create(['name' => 'Exercise', 'target_time' => '06:30']);

        Routine::factory()->count(3)->subTaskOf($exercise)
            ->sequence(
                ['name' => 'Push-ups', 'sort_order' => 1],
                ['name' => 'Run 2 km', 'sort_order' => 2],
                ['name' => 'Stretch', 'sort_order' => 3],
            )
            ->create();

        // 5 random routines (names and times from RoutineFactory::definition()).
        Routine::factory()->count(5)->for($me)->create();

        // 1 inactive routine, to test the grey row and the "Part of" dropdown.
        Routine::factory()->for($me)->inactive()->create(['name' => 'Meditate', 'target_time' => '07:00']);
    }
}
