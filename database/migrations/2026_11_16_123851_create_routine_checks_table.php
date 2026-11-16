<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per tick: this routine was done on this day.
     */
    public function up(): void
    {
        Schema::create('routine_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->date('done_on');
            $table->timestamps();

            // A routine can be ticked only once per day (the database enforces it).
            // Also serves the sheet query "checks for these routines between Mon and Sun".
            $table->unique(['routine_id', 'done_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_checks');
    }
};
