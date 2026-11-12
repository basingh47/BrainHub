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

            {{-- Flash message (set by ->with('status', ...) in the controller, shown once) --}}
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

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

            {{-- ④ Filters --}}
            <form method="GET" action="{{ route('daily-tasks.index') }}"
                  class="flex flex-wrap items-end gap-4 rounded-lg border border-gray-200 bg-white p-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                    <input id="date" name="date" type="date" value="{{ request('date', today()->toDateString()) }}"
                           class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status"
                            class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>In progress</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    </select>
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category" name="category"
                            class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="office" @selected(request('category') === 'office')>Office</option>
                        <option value="learning" @selected(request('category') === 'learning')>Learning</option>
                        <option value="personal" @selected(request('category') === 'personal')>Personal</option>
                    </select>
                </div>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Filter
                </button>
                <a href="{{ route('daily-tasks.index') }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Reset
                </a>
            </form>

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
                                    <button type="button" class="font-semibold text-green-700 hover:underline">Complete</button>
                                    <button type="button" class="font-semibold text-indigo-700 hover:underline">Move</button>
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
                                        <p @class(['font-semibold', 'text-gray-500 line-through' => $isCompleted, 'text-gray-900' => ! $isCompleted])>
                                            {{ $task->title }}
                                        </p>
                                        @if ($isCompleted)
                                            <p class="text-xs text-gray-600">Completed at {{ $task->completed_at->format('H:i') }}</p>
                                        @elseif ($task->description)
                                            <p class="text-xs text-gray-600">{{ $task->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ ucfirst($task->category) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $priorityClasses[$task->priority] }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $task->due_time ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses[$task->status] }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @unless ($isCompleted)
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST" action="{{ route('daily-tasks.update', $task->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="title" value="{{ $task->title }}">
                                                    <input type="hidden" name="description" value="{{ $task->description }}">
                                                    <input type="hidden" name="category" value="{{ $task->category }}">
                                                    <input type="hidden" name="priority" value="{{ $task->priority }}">
                                                    <input type="hidden" name="due_date" value="{{ $task->due_date }}">
                                                    <input type="hidden" name="due_time" value="{{ $task->due_time }}">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit"
                                                            class="rounded-md border border-green-300 bg-green-50 px-3 py-1.5 font-semibold text-green-800 hover:bg-green-100">
                                                        Complete
                                                    </button>
                                                </form>
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <button type="button" aria-label="More actions"
                                                                class="rounded-md border border-indigo-300 bg-indigo-50 px-3 py-1.5 font-bold text-indigo-800 hover:bg-indigo-100">
                                                            •••
                                                        </button>
                                                    </x-slot>
                                                    <x-slot name="content">
                                                        <x-dropdown-link :href="route('daily-tasks.edit', $task->id)">Edit task</x-dropdown-link>
                                                        <x-dropdown-link href="#">Move to another date</x-dropdown-link>
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
                                            </div>
                                        @endunless
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
    </div>

</x-app-layout>
