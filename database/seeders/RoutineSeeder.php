<?php

namespace Database\Seeders;

use App\Models\Routine;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Local dummy data for the Daily Routine page (same as the mockups).
 * NEVER run in production: it empties the routine tables first.
 */
class RoutineSeeder extends Seeder
{
    public function run(): void
    {
        // Empty the 4 tables. Foreign keys block TRUNCATE, so switch them off for a moment.
        Schema::disableForeignKeyConstraints();
        RoutineCheck::truncate();
        DB::table('routine_week_items')->truncate();
        RoutineWeek::truncate();
        Routine::truncate();
        Schema::enableForeignKeyConstraints();

        // ── Routines (and sub-tasks) ──
        $wake = Routine::factory()->create(['name' => 'Wake up', 'target_time' => '06:00', 'sort_order' => 1]);
        $exercise = Routine::factory()->create(['name' => 'Exercise', 'target_time' => '06:30', 'sort_order' => 2]);
        $pushUps = Routine::factory()->subTaskOf($exercise)->create(['name' => '20 push-ups', 'sort_order' => 1]);
        $run = Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run', 'sort_order' => 2]);
        $stretch = Routine::factory()->subTaskOf($exercise)->create(['name' => 'Stretching', 'sort_order' => 3]);
        $read = Routine::factory()->create(['name' => 'Read 20 min', 'target_time' => '21:00', 'sort_order' => 3]);
        Routine::factory()->create(['name' => 'Drink 2 L water', 'target_time' => null, 'sort_order' => 4]);
        Routine::factory()->create(['name' => 'Plan tomorrow', 'target_time' => '22:00', 'sort_order' => 5]);
        Routine::factory()->inactive()->create(['name' => 'Meditate', 'target_time' => '07:00', 'sort_order' => 6]);

        // ── Weekly sheets: last week and this week, with the same 3 routines ──
        $lastWeek = RoutineWeek::factory()->create(['week_start' => today()->startOfWeek()->subWeek()]);
        $thisWeek = RoutineWeek::factory()->current()->create();

        $onSheet = [$wake->id, $exercise->id, $read->id];
        $lastWeek->routines()->attach($onSheet);
        $thisWeek->routines()->attach($onSheet);

        // ── Ticks: only leaf routines (no sub-tasks), only days up to today ──
        $leaves = [$wake, $pushUps, $run, $stretch, $read];
        $day = $lastWeek->week_start->copy();

        while ($day->lte(today())) {
            foreach ($leaves as $routine) {
                if (fake()->boolean(70)) {  // about 70% of boxes ticked
                    RoutineCheck::factory()->create([
                        'routine_id' => $routine->id,
                        'done_on' => $day->toDateString(),
                    ]);
                }
            }
            $day->addDay();
        }
    }
}
