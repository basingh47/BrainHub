<x-app-layout>

    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">Add task</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">

            {{-- POST /daily-tasks → DailyTaskController@store --}}
            <form method="POST" action="{{ route('daily-tasks.store') }}"
                  class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
                @csrf

                {{-- Title (required) --}}
                <div>
                    <x-input-label for="title" value="Title *" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                  :value="old('title')" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                {{-- Description (optional) --}}
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Category (optional) --}}
                    <div>
                        <x-input-label for="category" value="Category" />
                        <select id="category" name="category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— None —</option>
                            <option value="office" @selected(old('category') === 'office')>Office</option>
                            <option value="learning" @selected(old('category') === 'learning')>Learning</option>
                            <option value="personal" @selected(old('category') === 'personal')>Personal</option>
                        </select>
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    {{-- Priority (default medium) --}}
                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" name="priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="high" @selected(old('priority') === 'high')>High</option>
                            <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                            <option value="low" @selected(old('priority') === 'low')>Low</option>
                        </select>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>

                    {{-- Due date (required, today or later) --}}
                    <div>
                        <x-input-label for="due_date" value="Due date *" />
                        <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full"
                                      :value="old('due_date', today()->toDateString())"
                                      :min="today()->toDateString()" required />
                        <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                    </div>

                    {{-- Due time (optional) --}}
                    <div>
                        <x-input-label for="due_time" value="Due time" />
                        <x-text-input id="due_time" name="due_time" type="time" class="mt-1 block w-full"
                                      :value="old('due_time')" />
                        <x-input-error :messages="$errors->get('due_time')" class="mt-2" />
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('daily-tasks.index') }}"
                       class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <x-primary-button>Save task</x-primary-button>
                </div>
            </form>

        </div>
    </div>

</x-app-layout>
