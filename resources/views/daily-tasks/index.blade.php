@extends('daily-tasks.layouts.daily-tasks-layout')


@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Flash message: colour + icon by type (set by ->with('flash', [...]) in the controller) --}}
            <x-flash />

            {{-- Move errors: the popup is closed after the redirect, so show the error here --}}
            @error('due_date')
                <div role="alert" class="flex items-center gap-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <span aria-hidden="true" class="font-bold">✕</span>
                    <p>{{ $message }}</p>
                </div>
            @enderror

            @php
                // Badge colours — will move into <x-status-badge> / <x-priority-badge> components in Step D.
                $priorityClasses = [
                    'high' => 'bg-red-100 text-red-800',
                    'medium' => 'bg-yellow-100 text-yellow-800',
                    'low' => 'bg-gray-100 text-gray-700',
                ];
                $statusClasses = [
                    'pending' => 'bg-gray-100 text-gray-700',
                    'in_progress' => 'bg-blue-100 text-blue-800',
                    'completed' => 'bg-green-100 text-green-800',
                ];
            @endphp

            {{-- ③ Stat cards --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-600">Today</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['today'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-600">In progress</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['in_progress'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="mt-1 text-3xl font-bold text-green-700">{{ $stats['completed'] }}</p>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm text-red-800">Overdue</p>
                    <p class="mt-1 text-3xl font-bold text-red-800">{{ $stats['overdue'] }}</p>
                </div>
            </div>

            {{-- ⑤ Overdue tasks --}}
            @if ($overdueTasks->isNotEmpty())
                <section class="overflow-hidden rounded-lg border border-red-200 bg-red-50">
                    <h3 class="border-b border-red-200 px-4 py-3 font-semibold text-red-800">
                        Overdue ({{ $overdueTasks->count() }}) · finish or move these first
                    </h3>
                    <ul class="divide-y divide-red-200">
                        @foreach ($overdueTasks as $task)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                <span class="font-semibold text-gray-900">{{ $task->title }}</span>
                                <div class="flex flex-wrap items-center gap-6 text-sm">
                                    <span class="text-gray-700">{{ ucfirst($task->category) }}</span>
                                    <span class="font-semibold text-red-700">Due {{ $task->due_date->format('D j M') }}</span>
                                    <x-daily-tasks.complete-button :task="$task" variant="link" />
                                    <button type="button" popovertarget="move-{{ $task->id }}"
                                            class="font-semibold text-indigo-700 hover:underline">Move</button>
                                    <x-daily-tasks.move-popover :task="$task" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- ⑥ Today's tasks table --}}
            <section class="rounded-lg border border-gray-200 bg-white">
                <h3 class="px-4 py-3 text-lg font-semibold text-gray-900">Today's tasks</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Task</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3">Due time</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($tasks as $task)
                                @php $isCompleted = $task->status === 'completed'; @endphp
                                <tr @class(['bg-gray-50' => $isCompleted])>
                                    <td class="px-4 py-3">
                                        <div class="flex items-start gap-3">
                                            {{-- State circle: ○ open, ✓ green when completed (icon only, not a button) --}}
                                            @if ($isCompleted)
                                                <span aria-hidden="true"
                                                      class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-600 text-xs font-bold text-white">✓</span>
                                            @else
                                                <span aria-hidden="true"
                                                      class="mt-0.5 h-5 w-5 shrink-0 rounded-full border-2 border-gray-300"></span>
                                            @endif

                                            <div>
                                                <p @class([
                                                    'font-semibold',
                                                    'text-gray-500 line-through decoration-gray-300' => $isCompleted,
                                                    'text-gray-900' => ! $isCompleted,
                                                ])>
                                                    {{ $task->title }}
                                                </p>
                                                @if ($isCompleted)
                                                    <span class="mt-1 inline-block rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">
                                                        ✓ Done {{ $task->completed_at?->format('H:i') }}
                                                    </span>
                                                @elseif ($task->description)
                                                    <p class="text-xs text-gray-600">{{ $task->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td @class(['px-4 py-3 text-gray-700', 'opacity-50' => $isCompleted])>{{ ucfirst($task->category) }}</td>
                                    <td @class(['px-4 py-3', 'opacity-50' => $isCompleted])>
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $priorityClasses[$task->priority] }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td @class(['px-4 py-3 text-gray-700', 'opacity-50' => $isCompleted])>{{ $task->due_time ?? '—' }}</td>
                                    <td @class(['px-4 py-3', 'opacity-50' => $isCompleted])>
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses[$task->status] }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                                {{-- Main next step: Complete for open tasks, Reopen for completed ones --}}
                                                @if ($isCompleted)
                                                    <x-daily-tasks.reopen-button :task="$task" />
                                                @else
                                                    <x-daily-tasks.complete-button :task="$task" />
                                                @endif

                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <button type="button" aria-label="More actions"
                                                                class="rounded-md border border-indigo-300 bg-indigo-50 px-3 py-1.5 font-bold text-indigo-800 hover:bg-indigo-100">
                                                            •••
                                                        </button>
                                                    </x-slot>
                                                    <x-slot name="content">
                                                        <x-dropdown-link :href="route('daily-tasks.edit', $task->id)">Edit task</x-dropdown-link>
                                                        {{-- Plan rule 4: a completed task cannot be moved → no Move item --}}
                                                        @unless ($isCompleted)
                                                            {{-- A button, not a link: a link would send GET (405). It only opens the popup. --}}
                                                            <button type="button" popovertarget="move-{{ $task->id }}"
                                                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none">
                                                                Move to another date
                                                            </button>
                                                        @endunless
                                                        <form method="POST" action="{{ route('daily-tasks.destroy', $task->id) }}" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                            @csrf
                                                            @method('DELETE')
                                                               <button type="submit"
                                                                class="block w-full px-4 py-2 text-start text-sm text-red-700 hover:bg-gray-100">
                                                                    Delete
                                                                </button>
                                                        </form>
                                                    </x-slot>
                                                </x-dropdown>

                                                {{-- Popup lives OUTSIDE the dropdown: the dropdown hides itself after a click, and would hide the popup too. --}}
                                                @unless ($isCompleted)
                                                    <x-daily-tasks.move-popover :task="$task" />
                                                @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-600">No tasks for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
@endsection



