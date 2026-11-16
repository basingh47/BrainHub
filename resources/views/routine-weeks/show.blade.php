@extends('routine-weeks.layouts.routine-weeks-layout')

@section('content')
    <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

        {{-- Week navigation --}}
        <nav class="flex flex-wrap items-center justify-between gap-3 text-sm font-semibold" aria-label="Weeks">
            <a href="{{ route('routine-weeks.show', $previousWeek) }}" class="text-indigo-700 hover:underline">← Previous week</a>

            @unless ($isCurrentWeek)
                <a href="{{ route('daily-routine') }}" class="text-indigo-700 hover:underline">This week</a>
            @endunless

            @if ($isCurrentWeek)
                <span class="text-gray-400">Next week →</span>
            @else
                <a href="{{ route('routine-weeks.show', $nextWeek) }}" class="text-indigo-700 hover:underline">Next week →</a>
            @endif
        </nav>

        {{-- Tick errors (e.g. "You cannot tick a future day.") come back here after the redirect --}}
        @if ($errors->any())
            <div role="alert" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        @if (! $week)
            {{-- No sheet was created for this week --}}
            <section class="rounded-lg border border-gray-200 bg-white p-8 text-center">
                <h3 class="text-lg font-semibold text-gray-900">No sheet for this week</h3>
                <p class="mt-1 text-sm text-gray-600">
                    @if ($isCurrentWeek)
                        Creating this week's sheet comes in the next step.
                    @else
                        No routines were tracked this week.
                    @endif
                </p>
            </section>
        @else
            <section class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Routine</th>
                            @foreach ($days as $day)
                                <th @class(['px-2 py-3 text-center', 'bg-indigo-50 text-indigo-800' => $day->isToday(), 'text-gray-400' => $day->isFuture()])>
                                    {{ $day->isToday() ? 'Today' : $day->format('D') }}
                                    <span class="block text-base normal-case">{{ $day->format('j') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td @class(['py-3 pr-4', 'pl-4' => ! $row['sub'], 'pl-10' => $row['sub']])>
                                    <span @class(['font-semibold text-gray-900' => ! $row['sub'], 'text-gray-700' => $row['sub']])>
                                        {{ $row['sub'] ? '└ ' : '' }}{{ $row['name'] }}
                                    </span>
                                    @if ($row['time'])
                                        <span class="block text-xs text-gray-500">{{ $row['time'] }}</span>
                                    @endif
                                </td>
                                @foreach ($row['cells'] as $cell)
                                    <td @class(['px-2 py-2 text-center', 'bg-indigo-50/50' => $days[$loop->index]->isToday()])>
                                        <x-routines.check-cell :cell="$cell" />
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-600">This sheet has no routines yet.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="bg-gray-50 text-sm font-semibold text-gray-700">
                        <tr>
                            <td class="px-4 py-3">Done per day</td>
                            @foreach ($totals as $total)
                                <td class="px-2 py-3 text-center">{{ $total ?? '—' }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </section>
        @endif
    </div>
@endsection
