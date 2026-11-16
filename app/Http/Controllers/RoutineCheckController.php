<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoutineCheckRequest;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use Illuminate\Http\RedirectResponse;

/**
 * A tick is a row in routine_checks: tick = create the row, untick = delete it.
 */
class RoutineCheckController extends Controller
{
    /**
     * Tick: POST /routine-checks (routine_id + done_on).
     */
    public function store(StoreRoutineCheckRequest $request): RedirectResponse
    {
        // Runs only if StoreRoutineCheckRequest's rules passed.
        RoutineCheck::create($request->validated());

        return back();
    }

    /**
     * Untick: DELETE /routine-checks/{routineCheck}.
     */
    public function destroy(RoutineCheck $routineCheck): RedirectResponse
    {
        // Old weeks are read-only (plan rule 3) → 403 Forbidden.
        abort_unless(RoutineWeek::isEditableDay($routineCheck->done_on), 403);

        $routineCheck->delete();

        return back();
    }
}
