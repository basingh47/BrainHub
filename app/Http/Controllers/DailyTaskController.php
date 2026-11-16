<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveDailyTaskRequest;
use App\Http\Requests\StoreDailyTaskRequest;
use App\Http\Requests\UpdateDailyTaskRequest;
use App\Models\DailyTask;
use Illuminate\Http\RedirectResponse;

class DailyTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Today's tasks: open ones first, completed at the bottom; then earliest due time first.
        $tasks = DailyTask::whereDate('due_date', today())
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('due_time')
            ->get();

        // Overdue = due before today and not completed (computed, not stored).
        $overdueTasks = DailyTask::whereDate('due_date', '<', today())
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->get();

        // Counts for the stat cards, calculated from the collections.
        $stats = [
            'today' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $overdueTasks->count(),
        ];

        return view('daily-tasks.index', compact('tasks', 'overdueTasks', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only shows the empty form. Saving happens in store().
        return view('daily-tasks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDailyTaskRequest $request)
    {
        // Runs only if StoreDailyTaskRequest validation passed.
        $task = DailyTask::create($request->validated());

        // PRG pattern: redirect so F5 doesn't submit the form again.
        return $this->backToList('success', "“{$task->title}” added.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $task = DailyTask::findOrFail($id);

        return view('daily-tasks.edit', compact('task'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDailyTaskRequest $request, string $id)
    {
        $task = DailyTask::findOrFail($id);
        $data = $request->validated();
        $data['completed_at'] = $data['status'] === 'completed'
        ? ($task->completed_at ?? now())
        : null;

        $task->update($data);

        return $this->backToList('info', "“{$task->title}” updated.");
    }

    /**
     * Mark a task as completed. No input → no Form Request needed.
     */
    public function complete(string $id)
    {
        $task = DailyTask::findOrFail($id);

        $task->markCompleted();

        return $this->backToList('success', "“{$task->title}” completed.");
    }

    /**
     * Reopen a completed task (undo Complete). No input → no Form Request needed.
     */
    public function reopen(string $id)
    {
        $task = DailyTask::findOrFail($id);

        $task->markIncomplete();

        return $this->backToList('info', "“{$task->title}” reopened.");
    }

    /**
     * Move (carry forward) a task to another date.
     * Same task, new due_date — never a copy (plan rule 2).
     */
    public function move(MoveDailyTaskRequest $request, string $id)
    {
        $task = DailyTask::findOrFail($id);

        $task->update(['due_date' => $request->validated('due_date')]);

        return $this->backToList('info', "“{$task->title}” moved to {$task->due_date->format('D j M')}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = DailyTask::findOrFail($id);
        $task->delete();

        // The model object still holds the title after delete(), so we can use it in the message.
        return $this->backToList('danger', "“{$task->title}” deleted.");
    }

    /**
     * Redirect to the list with a flash message (PRG pattern).
     * $type: success | info | warning | danger → colour + icon in <x-flash>.
     */
    private function backToList(string $type, string $message): RedirectResponse
    {
        return redirect()
            ->route('daily-tasks.index')
            ->with('flash', ['type' => $type, 'message' => $message]);
    }
}
