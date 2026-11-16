@props(['task'])

@php
    // Overdue task → suggest today. Task due today or later → suggest tomorrow.
    $suggestedDate = $task->due_date->lt(today()) ? today() : today()->addDay();
@endphp

{{--
    Move popup — HTML Popover API (no JavaScript).
    Hidden until a button with popovertarget="move-{{ $task->id }}" is clicked.
    Esc or clicking outside closes it (built into the browser).
--}}
<div popover id="move-{{ $task->id }}"
     class="m-auto w-80 rounded-lg border border-gray-200 bg-white p-5 text-left shadow-xl backdrop:bg-gray-900/40">

    {{-- PATCH /daily-tasks/{id}/move → DailyTaskController@move --}}
    <form method="POST" action="{{ route('daily-tasks.move', $task) }}" class="space-y-4">
        @csrf
        @method('PATCH')

        <div>
            <h3 class="font-semibold text-gray-900">Move task</h3>
            <p class="text-sm text-gray-700">{{ $task->title }}</p>
            <p class="text-xs text-gray-500">Current due: {{ $task->due_date->format('D j M') }}</p>
        </div>

        <div>
            <label for="move-date-{{ $task->id }}" class="block text-sm font-medium text-gray-700">New date</label>
            <input id="move-date-{{ $task->id }}" name="due_date" type="date" required
                   min="{{ today()->toDateString() }}"
                   value="{{ $suggestedDate->toDateString() }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="flex justify-end gap-2">
            {{-- type="button" → closes the popup, does NOT submit the form --}}
            <button type="button" popovertarget="move-{{ $task->id }}" popovertargetaction="hide"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Move
            </button>
        </div>
    </form>
</div>
