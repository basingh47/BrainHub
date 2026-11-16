<x-app-layout>

    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">Edit task</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">

            {{-- PUT /daily-tasks/{id} → DailyTaskController@update --}}
            <form method="POST" action="{{ route('daily-tasks.update', $task) }}"
                  class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
                @csrf
                @method('PUT') {{-- HTML forms only send GET/POST; this hidden _method field tells Laravel "PUT" --}}

                {{-- Title (required) --}}
                <div>
                    <x-input-label for="title" value="Title *" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                  :value="old('title', $task->title)" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                {{-- Description (optional) --}}
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $task->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Category (optional) --}}
                    <div>
                        <x-input-label for="category" value="Category" />
                        <select id="category" name="category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— None —</option>
                            <option value="office" @selected(old('category', $task->category) === 'office')>Office</option>
                            <option value="learning" @selected(old('category', $task->category) === 'learning')>Learning</option>
                            <option value="personal" @selected(old('category', $task->category) === 'personal')>Personal</option>
                        </select>
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    {{-- Priority --}}
                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" name="priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="high" @selected(old('priority', $task->priority) === 'high')>High</option>
                            <option value="medium" @selected(old('priority', $task->priority) === 'medium')>Medium</option>
                            <option value="low" @selected(old('priority', $task->priority) === 'low')>Low</option>
                        </select>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>

                    {{-- Due date (required). No "min": an overdue task must still be editable. --}}
                    <div>
                        <x-input-label for="due_date" value="Due date *" />
                        <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full"
                                      :value="old('due_date', $task->due_date->toDateString())" required />
                        <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                    </div>

                    {{-- Due time (optional). DB gives "14:30:00" → cut to "14:30" for the input. --}}
                    <div>
                        <x-input-label for="due_time" value="Due time" />
                        <x-text-input id="due_time" name="due_time" type="time" class="mt-1 block w-full"
                                      :value="old('due_time', $task->due_time ? substr($task->due_time, 0, 5) : null)" />
                        <x-input-error :messages="$errors->get('due_time')" class="mt-2" />
                    </div>

                    {{-- Status (edit only — a new task always starts as pending) --}}
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="pending" @selected(old('status', $task->status) === 'pending')>Pending</option>
                            <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>In progress</option>
                            <option value="completed" @selected(old('status', $task->status) === 'completed')>Completed</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('daily-tasks.index') }}"
                       class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <x-primary-button>Update task</x-primary-button>
                </div>
            </form>

        </div>
    </div>

</x-app-layout>
