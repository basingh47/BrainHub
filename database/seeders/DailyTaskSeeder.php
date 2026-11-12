<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Database\Factories\DailyTaskFactory;

class DailyTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DB::table('daily_tasks')->insert([
        //     [
        //         'title' => 'Team meeting',
        //         'description' => 'Discuss project updates',
        //         'category' => 'office',
        //         'priority' => 'medium',
        //         'due_date' => today(),
        //         'due_time' => '14:00',
        //         'status' => 'pending',
        //         'completed_at' => null,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'title' => 'Grocery shopping',
        //         'description' => 'Buy ingredients for dinner',
        //         'category' => 'personal',
        //         'priority' => 'low',
        //         'due_date' => today(),
        //         'due_time' => null,
        //         'status' => 'pending',
        //         'completed_at' => null,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     // Add more tasks as needed
        // ]);
        DB::table('daily_tasks')->truncate();
        DailyTaskFactory::new()->times(10)->create(); // Create 10 new daily tasks
    }
}
