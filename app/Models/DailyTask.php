<?php

namespace App\Models;

use Database\Factories\DailyTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'description',
    'category',
    'priority',
    'due_date',
    'due_time',
    'status',
    'completed_at',
])]
class DailyTask extends Model
{
    /** @use HasFactory<DailyTaskFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Mark the task as completed (plan rule 5).
     * Idempotent: if it is already completed, keep the original completed_at.
     */
    public function markCompleted(): void
    {
        if ($this->status === 'completed') {
            return;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Reopen a completed task (undo Complete).
     * Back to 'pending' — the previous status is not stored. Idempotent.
     */
    public function markIncomplete(): void
    {
        if ($this->status !== 'completed') {
            return;
        }

        $this->update([
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }
}
