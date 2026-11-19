@extends('routines.layouts.routine-layout')

@section('title', 'Edit routine')
@section('subtitle', $routine->parent_id ? 'Sub-task of '.$routine->parent->name : 'Top-level routine')

@section('actions')
    <a href="{{ route('routines.index') }}" class="mr-3 text-sm font-semibold text-indigo-700 hover:underline">← Back to routines</a>
@endsection

@section('content')
    <div class="mx-auto max-w-xl">

        {{-- PATCH /routines/{id} → RoutineController@update --}}
        <form method="POST" action="{{ route('routines.update', $routine) }}"
              class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @method('PATCH')

            {{-- Name (required). old('name', default): the typed value after an error, otherwise the saved one --}}
            <div>
                <x-input-label for="name" value="Name *" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $routine->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            {{-- Target time (optional). The DB gives "06:30:00"; <input type="time"> wants "06:30" --}}
            <div>
                <x-input-label for="target_time" value="Target time" />
                <x-text-input id="target_time" name="target_time" type="time" class="mt-1 block w-full"
                              :value="old('target_time', $routine->target_time ? substr($routine->target_time, 0, 5) : '')" />
                <p class="mt-1 text-xs text-gray-500">Optional. Routines are sorted by time on the sheet.</p>
                <x-input-error :messages="$errors->get('target_time')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('routines.index') }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <x-primary-button>Save changes</x-primary-button>
            </div>
        </form>
    </div>
@endsection
