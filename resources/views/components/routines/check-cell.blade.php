{{--
    One box of the weekly sheet.
    $cell = ['status' => done|partial|open|parent|locked, 'routine_id', 'date', 'check_id', 'editable']
    Editable boxes are tiny forms: ☐ → POST (tick), ✓ → DELETE (untick). Everything else is display only.
--}}
@props(['cell'])

@if ($cell['editable'] && $cell['status'] === 'open')
    {{-- Tick: create a routine_checks row --}}
    <form method="POST" action="{{ route('routine-checks.store') }}" class="inline-block">
        @csrf
        <input type="hidden" name="routine_id" value="{{ $cell['routine_id'] }}">
        <input type="hidden" name="done_on" value="{{ $cell['date'] }}">
        <button type="submit" aria-label="Not done – click to tick"
                class="h-8 w-8 rounded-md border-2 border-gray-400 bg-white hover:border-indigo-500 hover:bg-indigo-50"></button>
    </form>
@elseif ($cell['editable'] && $cell['status'] === 'done' && $cell['check_id'])
    {{-- Untick: delete that routine_checks row --}}
    <form method="POST" action="{{ route('routine-checks.destroy', $cell['check_id']) }}" class="inline-block">
        @csrf
        @method('DELETE')
        <button type="submit" aria-label="Done – click to untick"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-700 text-white hover:bg-green-800">✓</button>
    </form>
@else
    {{-- Display only: old weeks, future days, and parents (computed from sub-tasks) --}}
    @switch($cell['status'])
        @case('done')
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-700 text-white" aria-label="Done">✓</span>
            @break
        @case('partial')
            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border-2 border-amber-600 bg-amber-100 px-1 text-xs font-bold text-amber-800"
                  aria-label="Partly done: {{ $cell['progress'] }} sub-tasks">{{ $cell['progress'] }}</span>
            @break
        @case('open')
            <span class="inline-block h-8 w-8 rounded-md border-2 border-gray-300 bg-white" aria-label="Not done"></span>
            @break
        @case('parent')
            <span class="inline-block h-8 w-8 rounded-md border-2 border-gray-300 bg-white" aria-label="Not done"></span>
            @break
        @default
            <span class="inline-block h-8 w-8 rounded-md border-2 border-dashed border-gray-200 bg-gray-50" aria-label="Locked"></span>
    @endswitch
@endif
