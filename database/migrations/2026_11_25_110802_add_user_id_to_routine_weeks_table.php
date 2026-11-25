<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every weekly sheet an owner, and allow one sheet per week PER USER (was: per app).
     */
    public function up(): void
    {
        // 1. Add the column as nullable, so existing rows don't break the foreign key.
        Schema::table('routine_weeks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // 2. Existing rows belong to the first account (the app was single-user until now).
        $ownerId = DB::table('users')->orderBy('id')->value('id');

        if ($ownerId) {
            DB::table('routine_weeks')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }

        // 3. Owner required + one sheet per week per user.
        Schema::table('routine_weeks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropUnique(['week_start']);
            $table->unique(['user_id', 'week_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routine_weeks', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'week_start']);
            $table->unique(['week_start']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
