<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();

            // Sub-task → points to its parent routine. null = top-level routine.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('routines')       // FK to this same table (self-reference)
                ->cascadeOnDelete();            // delete parent → its sub-tasks go too

            $table->string('name');
            $table->time('target_time')->nullable();       // e.g. 06:00, used for sorting
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);   // deactivate instead of delete
            $table->timestamps();

            $table->index(['is_active', 'parent_id']);     // the sheet asks "active top-level routines"

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
