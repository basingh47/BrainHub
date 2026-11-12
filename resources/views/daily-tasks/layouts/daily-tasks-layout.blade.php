<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daily Tasks</h2>
                <p class="text-sm text-gray-600">{{ now()->format('l, j F Y') }}</p>
            </div>

            <button type="button" id ="openAddTaskModal"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                + Add task
            </button>
        </div>
    </x-slot>
    <div class="py-8">
        @yield('content')
    </div>
    @include('daily-tasks.partials.task-modal')
</x-app-layout>