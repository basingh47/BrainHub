<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RoutineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'name',
    'target_time',
    'sort_order',
    'is_active',
])]
class Routine extends Model
{
    /** @use HasFactory<RoutineFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',   // DB gives 1/0 → PHP gets true/false
            'sort_order' => 'integer',
        ];
    }

    /**
     * The parent routine (for a sub-task). Null for a top-level routine.
     *
     * @return BelongsTo<Routine, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Routine::class, 'parent_id');
    }

    /**
     * The sub-tasks of this routine, in their chosen order.
     *
     * @return HasMany<Routine, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Routine::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * The ticks of this routine (one per day it was done).
     *
     * @return HasMany<RoutineCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(RoutineCheck::class);
    }

    /**
     * The weekly sheets this routine is on (via the routine_week_items pivot).
     *
     * @return BelongsToMany<RoutineWeek, $this>
     */
    public function weeks(): BelongsToMany
    {
        return $this->belongsToMany(RoutineWeek::class, 'routine_week_items');
    }

    /**
     * Was this routine ticked on that day?
     * Uses the already-loaded checks (eager loaded by the controller), so no extra query.
     */
    public function isDoneOn(CarbonInterface $day): bool
    {
        return $this->checks->contains(fn (RoutineCheck $check) => $check->done_on->isSameDay($day));
    }

    /**
     * The routine's status on a day (plan rule 5):
     * - no sub-tasks:   done | open
     * - with sub-tasks: done (all sub-tasks done) | partial (some) | parent (none) — computed, never stored
     */
    public function statusOn(CarbonInterface $day): string
    {
        if ($this->children->isEmpty()) {
            return $this->isDoneOn($day) ? 'done' : 'open';
        }

        $doneCount = $this->doneChildrenOn($day);

        return match (true) {
            $doneCount === $this->children->count() => 'done',
            $doneCount > 0 => 'partial',
            default => 'parent',
        };
    }

    /**
     * How many sub-tasks were ticked on that day (e.g. 2 of Exercise's 3).
     */
    public function doneChildrenOn(CarbonInterface $day): int
    {
        return $this->children->filter(fn (Routine $child) => $child->isDoneOn($day))->count();
    }
}
