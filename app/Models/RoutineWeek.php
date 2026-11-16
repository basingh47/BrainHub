<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RoutineWeekFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $week_start Cast to a date (Monday) — documented here for Larastan.
 */
#[Fillable([
    'week_start',
])]
class RoutineWeek extends Model
{
    /** @use HasFactory<RoutineWeekFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start' => 'date',   // "2026-11-16" → Carbon (Monday)
        ];
    }

    /**
     * The routines on this weekly sheet (via the routine_week_items pivot).
     *
     * @return BelongsToMany<Routine, $this>
     */
    public function routines(): BelongsToMany
    {
        // Custom pivot name: Laravel's convention would be "routine_routine_week".
        return $this->belongsToMany(Routine::class, 'routine_week_items');
    }

    /**
     * The sheet of the week that contains $date, or null if no sheet was created.
     */
    public static function forDate(CarbonInterface $date): ?self
    {
        return static::whereDate('week_start', $date->copy()->startOfWeek())->first();
    }

    /**
     * Can boxes on this day be ticked / unticked? (plan rule 3)
     * Only days of the CURRENT week, up to today. Future days and old weeks: no.
     */
    public static function isEditableDay(CarbonInterface $day): bool
    {
        return $day->gte(today()->startOfWeek()) && $day->lte(today());
    }
}
