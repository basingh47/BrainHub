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
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();          // office, learning, personal
            $table->string('priority')->default('medium');   // high, medium, low
            $table->date('due_date');
            $table->time('due_time')->nullable();
            $table->string('status')->default('pending');    // pending, in_progress, completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();                            // created_at, updated_at

            // The list page always filters by date and status.
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_tasks');
    }
};
