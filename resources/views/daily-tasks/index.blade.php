<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daily Tasks</h2>
                <p class="text-sm text-gray-600">{{ now()->format('l, j F Y') }}</p>
            </div>

            <a href="{{ route('daily-tasks.create') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                + Add task
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- ③ Stat cards --}}
            {{-- ④ Filters --}}
            {{-- ⑤ Overdue tasks --}}
            {{-- ⑥ Today's tasks table --}}

        </div>
    </div>

</x-app-layout>
