<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class RoutineWeekController extends Controller
{
    /**
     * Menu link "Daily Routine" → this week's sheet.
     */
    public function current(): RedirectResponse
    {
        return redirect()->route('routine-weeks.show', today()->startOfWeek()->toDateString());
    }

    /**
     * The weekly sheet: routines × 7 days, read from the database.
     */
    public function show(string $date): View|RedirectResponse
    {
        // The URL date → the Monday of its week. A wrong date (e.g. 2026-13-45) → 404.
        try {
            $monday = Carbon::parse($date)->startOfWeek();
        } catch (Throwable) {
            abort(404);
        }

        // One URL per week: /routine-weeks/2026-11-18 → /routine-weeks/2026-11-16
        if ($monday->toDateString() !== $date) {
            return redirect()->route('routine-weeks.show', $monday->toDateString());
        }

        $sunday = $monday->copy()->endOfWeek();
        $days = collect(CarbonPeriod::create($monday, $sunday));

        $week = RoutineWeek::forDate($monday);

        // Eager loading: routines + sub-tasks + only THIS week's ticks, in a few queries
        // (instead of one query per box → the N+1 problem).
        $routines = $week
            ? $week->routines()
                ->with([
                    'checks' => fn ($query) => $query->whereBetween('done_on', [$monday, $sunday]),
                    'children.checks' => fn ($query) => $query->whereBetween('done_on', [$monday, $sunday]),
                    'children.children',  // sub-tasks have none, but statusOn() asks — load it once, not once per sub-task
                ])
                ->orderByRaw('target_time IS NULL')  // routines without a time go last
                ->orderBy('target_time')
                ->orderBy('sort_order')
                ->get()
            : collect();

        // One row per routine, then its sub-tasks underneath.
        $rows = [];
        foreach ($routines as $routine) {
            $rows[] = $this->row($routine, $days, false);

            foreach ($routine->children as $child) {
                $rows[] = $this->row($child, $days, true);
            }
        }

        // "Done per day" footer: top-level routines done ÷ total. Future days → null.
        $totals = $days->map(fn (Carbon $day) => $day->isFuture() ? null
            : $routines->filter(fn (Routine $routine) => $routine->statusOn($day) === 'done')->count().' / '.$routines->count());

        return view('routine-weeks.show', [
            'week' => $week,
            'days' => $days,
            'rows' => $rows,
            'totals' => $totals,
            'weekLabel' => $monday->format($monday->month === $sunday->month ? 'j' : 'j M').' – '.$sunday->format('j M Y'),
            'previousWeek' => $monday->copy()->subWeek()->toDateString(),
            'nextWeek' => $monday->copy()->addWeek()->toDateString(),
            'isCurrentWeek' => $monday->isSameDay(today()->startOfWeek()),
        ]);
    }

    /**
     * One table row: name, time, and one box (cell) for each of the 7 days.
     *
     * @param  Collection<int, Carbon>  $days
     * @return array{name: string, time: ?string, sub: bool, cells: array<int, array<string, mixed>>}
     */
    private function row(Routine $routine, Collection $days, bool $sub): array
    {
        return [
            'name' => $routine->name,
            'time' => $routine->target_time ? substr($routine->target_time, 0, 5) : null,  // "06:00:00" → "06:00"
            'sub' => $sub,
            'cells' => $days->map(fn (Carbon $day) => $this->cell($routine, $day))->all(),
        ];
    }

    /**
     * One box: what it shows, and what its form needs to tick / untick it.
     *
     * @return array{status: string, routine_id: int, date: string, check_id: ?int, editable: bool, progress: ?string}
     */
    private function cell(Routine $routine, Carbon $day): array
    {
        $isLeaf = $routine->children->isEmpty();  // only routines without sub-tasks can be ticked

        // The tick row for this day (needed to untick = delete it).
        $check = $isLeaf
            ? $routine->checks->first(fn (RoutineCheck $check) => $check->done_on->isSameDay($day))
            : null;

        return [
            'status' => $day->isFuture() ? 'locked' : $routine->statusOn($day),
            'routine_id' => $routine->id,
            'date' => $day->toDateString(),
            'check_id' => $check?->id,
            'editable' => $isLeaf && RoutineWeek::isEditableDay($day),
            // Parents show how many sub-tasks are done, e.g. "2/3".
            'progress' => $isLeaf ? null : $routine->doneChildrenOn($day).'/'.$routine->children->count(),
        ];
    }
}
