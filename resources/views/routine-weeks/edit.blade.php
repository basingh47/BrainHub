@extends('routine-weeks.layouts.routine-weeks-layout')

{{--
    TEMPORARY fake data for the design step.
    Next step deletes this block: edit() will send the real $weekLabel, $routines and $selectedIds.
--}}

@section('content')
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">

        {{-- PATCH /routine-weeks/{date} → RoutineWeekController@update --}}
        <form method="POST" action="{{ route('routine-weeks.update', $date) }}"
              class="rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @method('PATCH')

            <h3 class="text-lg font-semibold text-gray-900">Routines on this week's sheet</h3>
            <p class="mt-1 text-sm text-gray-600">Week {{ $weekLabel }}. Sub-tasks come with their routine.</p>

            <x-input-error :messages="$errors->get('routine_ids')" class="mt-3" />

            <div class="mt-4 space-y-2">
                @forelse ($routines as $routine)
                    @php $isChecked = in_array($routine->id, old('routine_ids', $selectedIds)); @endphp

                    {{-- <label> around the row: clicking anywhere on it ticks the box --}}
                    <label @class([
                        'flex cursor-pointer items-center gap-3 rounded-md px-4 py-3',
                        'bg-gray-50' => $isChecked,
                        'hover:bg-gray-50' => ! $isChecked,
                    ])>
                        {{-- name="routine_ids[]": the [] sends a LIST, e.g. [1, 2, 6] --}}
                        <input type="checkbox" name="routine_ids[]" value="{{ $routine->id }}" @checked($isChecked)
                               class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">

                        <span class="flex-1 text-sm">
                            <span @class(['font-semibold text-gray-900' => $isChecked, 'text-gray-700' => ! $isChecked])>{{ $routine->name }}</span>
                            @if ($routine->children->isNotEmpty())
                                <span class="text-gray-500">· {{ $routine->children->pluck('name')->join(', ') }}</span>
                            @endif
                        </span>

                        <span class="text-sm text-gray-500">{{ $routine->target_time ?? '—' }}</span>
                    </label>
                @empty
                    <p class="py-6 text-center text-sm text-gray-600">
                        No active routines. <a href="{{ route('routines.index') }}" class="font-semibold text-indigo-700 hover:underline">Add some first →</a>
                    </p>
                @endforelse
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('routine-weeks.show', $date) }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <x-primary-button>Save sheet</x-primary-button>
            </div>
        </form>
    </div>
@endsection
