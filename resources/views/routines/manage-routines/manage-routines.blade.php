@extends('routines.layouts.routine-layout')

@section('subtitle', "Your reusable routine items. Pick which ones go on each week's sheet.")

@section('content')

    <div class="mx-auto max-w-4xl">

        {{-- ① Top row: counts (left) + New routine button (right) --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-gray-600">
                {{-- real numbers come from the controller in step 2 --}}
                <span class="font-semibold text-gray-900">10</span> active ·
                <span class="font-semibold text-gray-900">20</span> inactive
            </p>

            {{-- popovertarget opens <div id="new-routine" popover> (added in a later step) --}}
            <button type="button" popovertarget="new-routine"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                + New routine
            </button>
        </div>

        {{-- ② toolbar (tabs + search) · ③ groups + cards · ④ inactive — next steps --}}

    </div>
@endsection