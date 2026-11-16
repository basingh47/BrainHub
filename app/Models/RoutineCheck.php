<?php

namespace App\Models;

use Database\Factories\RoutineCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $done_on Cast to a date — documented here for Larastan.
 */
#[Fillable([
    'routine_id',
    'done_on',
])]
class RoutineCheck extends Model
{
    /** @use HasFactory<RoutineCheckFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'done_on' => 'date',
        ];
    }

    /**
     * The routine that was ticked.
     *
     * @return BelongsTo<Routine, $this>
     */
    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }
}
