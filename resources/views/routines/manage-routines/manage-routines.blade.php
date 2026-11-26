@extends('routines.layouts.routine-layout')

@section('subtitle', "Your reusable routine items. Pick which ones go on each week's sheet.")

@section('content')

@php
$status = request('status', 'all'); // which tab is selected (from the URL)
$filters = [
'all' => 'All',
'active' => 'Active',
'inactive' => 'Inactive',
'archive' => 'Archive',
];
@endphp

<div>
    {{-- ① Toolbar: filter tabs (left) + New routine (right) --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">

        <nav class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm font-medium" aria-label="Filter routines">
            @foreach ($filters as $key => $label)
            <a href="{{ route('routines.index', ['status' => $key]) }}"
                @class([ 'rounded-md px-3 py-1.5' , 'bg-indigo-50 text-indigo-700'=> $status === $key,
                'text-gray-600 hover:text-gray-900' => $status !== $key,
                ])
                @if ($status === $key) aria-current="page" @endif>
                {{ $label }}
            </a>
            @endforeach
        </nav>

        <button type="button" popovertarget="new-routine"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + New routine
        </button>
    </div>

    {{-- ② Routines list: ONE outer box, one small box per routine --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between px-1">
            <h3 class="text-sm font-semibold text-gray-900">My routines</h3>
            <span class="text-xs text-gray-500">{{ $routines->count() }} routines</span>
        </div>

        <div class="space-y-2">
            @forelse ($routines as $routine)
            @php $time = $routine->short_time; @endphp

            <div @class([ 'flex items-center gap-4 rounded-lg border border-gray-200 px-4 py-3' , 'bg-gray-50'=> ! $routine->is_active,
                ])>

                {{-- Left: time pill, colour by part of day --}}
                <span @class([ 'w-16 shrink-0 rounded-md py-1 text-center font-mono text-sm font-bold' , 'bg-gray-100 text-gray-500'=> ! $time,
                    'bg-amber-50 text-amber-700' => $time && $time < '12:00' , 'bg-sky-50 text-sky-700'=> $time >= '12:00' && $time < '17:00' , 'bg-indigo-50 text-indigo-700'=> $time >= '17:00',
                            ])>
                            {{ $time ?? '—' }}
                </span>

                {{-- Middle: name + usage --}}
                <div class="min-w-0 flex-1">
                    <p @class(['truncate font-semibold', 'text-gray-900'=> $routine->is_active, 'text-gray-500' => ! $routine->is_active])>
                        {{ $routine->name }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $routine->is_used ? "on {$routine->weeks_count} sheets" : 'never used' }}
                        @unless ($routine->is_active) · archived @endunless
                    </p>
                </div>

                {{-- Right: actions --}}
                <div class="flex shrink-0 items-center gap-1 text-sm font-semibold">
                    @if ($routine->is_active)
                    <a href="{{ route('routines.edit', $routine) }}"
                        class="rounded-md px-3 py-1.5 text-indigo-700 hover:bg-indigo-50">Edit</a>

                    @if ($routine->is_used)
                    <form method="POST" action="{{ route('routines.deactivate', $routine) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-20 rounded-md py-1.5 text-center text-amber-700 hover:bg-amber-50">Archive</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('routines.destroy', $routine) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-20 rounded-md py-1.5 text-center text-red-700 hover:bg-red-50">Delete</button>
                    </form>
                    @endif
                    @else
                    <form method="POST" action="{{ route('routines.activate', $routine) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-20 rounded-md py-1.5 text-center text-indigo-700 hover:bg-indigo-50">Restore</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="px-1 py-8 text-center text-sm text-gray-600">No routines yet. Click “+ New routine” to add one.</p>
            @endforelse
        </div>
    </div>
</div>

{{--Popover content for adding new routine--}}
<div id="new-routine" popover
    class="m-auto w-full max-w-md rounded-lg bg-white p-6 shadow-xl backdrop:bg-gray-900/40 backdrop:backdrop-blur-sm">
    <h3 class="text-lg font-semibold text-gray-900">Add routine</h3>
    <form method="POST" action="{{ route('routines.store') }}" class="mt-4 space-y-5">
        @csrf

        {{-- Name (required) --}}
        <div>
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                placeholder="e.g. Drink 2 L water" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Part of: empty = top-level routine, a parent = sub-task --}}
        <div>
            <x-input-label for="parent_id" value="Part of" />
            <select id="parent_id" name="parent_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">None (top-level routine)</option>
                @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id')==$parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Choose a routine to make this a sub-task of it.</p>
            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
        </div>

        {{-- Target time (optional) --}}
        <div>
            <x-input-label for="target_time" value="Target time" />
            <x-text-input id="target_time" name="target_time" type="time" class="mt-1 block w-full"
                :value="old('target_time')" />
            <p class="mt-1 text-xs text-gray-500">Optional. Routines are sorted by time on the sheet.</p>
            <x-input-error :messages="$errors->get('target_time')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">Save routine</x-primary-button>
    </form>
</div>
@endsection