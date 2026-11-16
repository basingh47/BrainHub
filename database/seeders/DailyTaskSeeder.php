<?php

namespace Database\Seeders;

use Database\Factories\DailyTaskFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DailyTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Empty the table first, so running the seeder twice doesn't create duplicates.
        DB::table('daily_tasks')->truncate();

        DailyTaskFactory::new()->count(10)->create();
    }
}
