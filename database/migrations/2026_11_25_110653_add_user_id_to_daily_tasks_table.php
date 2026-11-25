<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every task an owner. Works on an empty table (local, CI) and on a table with data (live).
     */
    public function up(): void
    {
        // 1. Add the column as nullable, so existing rows don't break the foreign key.
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // 2. Existing rows belong to the first account (the app was single-user until now).
        $ownerId = DB::table('users')->orderBy('id')->value('id');

        if ($ownerId) {
            DB::table('daily_tasks')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }

        // 3. From now on every task must have an owner.
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
