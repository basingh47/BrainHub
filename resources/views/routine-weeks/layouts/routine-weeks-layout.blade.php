<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daily Routine</h2>
                <p class="text-sm text-gray-600">
                    Week {{ $weekLabel }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- href="#" for now: these routes are built in steps B and C --}}
                <a href="#"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Edit sheet
                </a>
                <a href="#"
                   class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Manage routines
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        @yield('content')
    </div>
</x-app-layout>
