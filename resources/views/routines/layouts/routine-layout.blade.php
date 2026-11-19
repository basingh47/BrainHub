<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">@yield('title', 'Manage routines')</h2>
                <p class="text-sm text-gray-600">@yield('subtitle')</p>
            </div>

            {{-- Every routines page leads back to the weekly sheet --}}
            <div class="flex flex-wrap items-center gap-2">
                @yield('actions')
                <a href="{{ route('daily-routine') }}" class="text-sm font-semibold text-indigo-700 hover:underline">
                    ← Back to this week
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-flash />
            @yield('content')
        </div>
    </div>
</x-app-layout>

