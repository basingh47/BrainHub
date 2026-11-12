<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyTask;
use App\Http\Requests\StoreDailyTaskRequest;
use App\Http\Requests\UpdateDailyTaskRequest;

class DailyTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TEMPORARY dummy data — replaced by a DailyTask query in Step B.
        // (object) makes each array behave like a model: $task->title
        $tasks = DailyTask::whereDate('due_date', today())->orderBy('due_time')->get();
        $overdueTasks = DailyTask::whereDate('due_date', '<', today())->where('status', '!=', 'completed')->get(); // Debugging: dump the collections to check their contents

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
        DailyTask::create($request->validated());

        // PRG pattern: redirect so F5 doesn't submit the form again.
        return redirect()
            ->route('daily-tasks.index')
            ->with('status', 'Task added.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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

        return redirect()
            ->route('daily-tasks.index')
            ->with('status', 'Task updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = DailyTask::findOrFail($id);
        $task->delete();

        return redirect()
            ->route('daily-tasks.index')
            ->with('status', 'Task deleted.');
    }
}
