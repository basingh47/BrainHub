@props(['task', 'variant' => 'button'])

{{--
    Complete button — PATCH /daily-tasks/{id}/complete → DailyTaskController@complete
    No input fields: the task id is in the URL, the server sets status + completed_at.
    variant: 'button' (bordered, for the table) or 'link' (text only, for the overdue box)
--}}
<form method="POST" action="{{ route('daily-tasks.complete', $task) }}">
    @csrf
    @method('PATCH')

    <button type="submit"
            @class([
                'rounded-md border border-green-300 bg-green-50 px-3 py-1.5 font-semibold text-green-800 hover:bg-green-100' => $variant === 'button',
                'font-semibold text-green-700 hover:underline' => $variant === 'link',
            ])>
        Complete
    </button>
</form>
