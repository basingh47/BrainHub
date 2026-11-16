<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table: which routines are on which weekly sheet (many-to-many).
     */
    public function up(): void
    {
        Schema::create('routine_week_items', function (Blueprint $table) {
            $table->foreignId('routine_week_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();

            // Both columns together are the key: a routine can be on a sheet only once.
            $table->primary(['routine_week_id', 'routine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_week_items');
    }
};
