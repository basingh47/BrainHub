@props(['task'])

{{--
    Reopen button (undo Complete) — PATCH /daily-tasks/{id}/reopen → DailyTaskController@reopen
    No input fields: the server sets status = pending and clears completed_at.
--}}
<form method="POST" action="{{ route('daily-tasks.reopen', $task) }}">
    @csrf
    @method('PATCH')

    <button type="submit"
            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50">
        Reopen
    </button>
</form>
