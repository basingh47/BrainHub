@extends('routines.layouts.routine-layout')

@section('subtitle', "Your reusable routine items. Pick which ones go on each week's sheet.")

@section('content')

@php
    $status  = request('status', 'all');          // which tab is selected (from the URL)
    $filters = [
        'all'      => 'All',
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'archive'  => 'Archive',
    ];
@endphp

<div>
    {{-- ① Toolbar: filter tabs (left) + New routine (right) --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">

        <nav class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm font-medium" aria-label="Filter routines">
            @foreach ($filters as $key => $label)
                <a href="{{ route('routines.index', ['status' => $key]) }}"
                   @class([
                       'rounded-md px-3 py-1.5',
                       'bg-indigo-50 text-indigo-700'      => $status === $key,
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

    {{-- ② Routines table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-600">
                <tr>
                    <th class="px-5 py-3 text-left">Routine</th>
                    <th class="px-5 py-3 text-left">Time</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>

            @forelse ($routines as $routine)
            {{-- One <tbody> per routine: the routine row and its sub-task rows stay together as one group --}}
            <tbody @class(['border-t border-gray-200', 'bg-gray-50 text-gray-400'=> ! $routine->is_active])>

                {{-- The routine row --}}
                <tr>
                    <td @class(['px-5 py-3 font-semibold', 'text-gray-900'=> $routine->is_active])>
                        {{ $routine->name }}

                        @if ($routine->children->isNotEmpty())
                        <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">
                            {{ $routine->children->count() }} sub-tasks
                        </span>
                        @endif

                        @unless ($routine->is_active)
                        <span class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600">Inactive</span>
                        @endunless
                    </td>

                    <td @class(['px-5 py-3', 'text-gray-600'=> $routine->is_active])>
                        {{ $routine->target_time ?? '—' }}
                    </td>

                    <td class="px-5 py-3 text-right">
                        @if ($routine->is_active)
                        <a href="{{ route('routines.edit', $routine->id) }}" class="font-semibold text-indigo-700 hover:underline">Edit</a>
                        {{-- Step 4: becomes a PATCH form --}}
                        <form action="{{ route('routines.deactivate', $routine->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="ml-3 font-semibold text-amber-700 hover:underline">Deactivate</button>
                        </form>
                        @else
                        {{-- Step 4: becomes a PATCH form --}}
                        <form action="{{ route('routines.activate', $routine->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="font-semibold text-indigo-700 hover:underline">Activate</button>
                        </form>
                        @endif
                    </td>
                </tr>

                {{-- Its sub-task rows (none for most routines) --}}
                @foreach ($routine->children as $child)
                <tr>
                    <td @class(['py-1.5 pl-10 pr-5', 'pb-3'=> $loop->last, 'text-gray-700' => $routine->is_active])>
                        └ {{ $child->name }}
                    </td>
                    <td></td>
                    <td @class(['px-5 py-1.5 text-right', 'pb-3'=> $loop->last])>
                        <a href="{{ route('routines.edit', $child->id) }}" class="font-semibold text-indigo-700 hover:underline">Edit</a>
                        {{-- Step 4: becomes a DELETE form --}}
                        <button type="button" class="ml-3 font-semibold text-amber-700 hover:underline">Remove</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
            @empty
            <tbody>
                <tr>
                    <td colspan="3" class="px-5 py-8 text-center text-gray-600">No routines yet. Add your first one →</td>
                </tr>
            </tbody>
            @endforelse
        </table>
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