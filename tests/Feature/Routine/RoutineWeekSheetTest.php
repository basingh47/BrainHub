<?php

namespace Tests\Feature\Routine;

use App\Models\Routine;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Create this week's sheet (POST /routine-weeks) and edit it (GET + PATCH /routine-weeks/{date}).
 *
 * "Today" is frozen on Wednesday 18 Nov 2026: this week = 16–22 Nov, last week = 9–15 Nov.
 */
class RoutineWeekSheetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-18 10:00'));
        $this->user = User::factory()->create();
    }

    private function thisWeek(): ?RoutineWeek
    {
        return RoutineWeek::forDate(today());
    }

    // ── Create ──────────────────────────────────────────────

    public function test_empty_week_shows_the_create_button(): void
    {
        $this->actingAs($this->user)
            ->get(route('routine-weeks.show', '2026-11-16'))
            ->assertSee("Create this week's sheet", false);   // false = compare raw HTML (the ' is not escaped in the page)
    }

    public function test_old_week_without_a_sheet_has_no_create_button(): void
    {
        $this->actingAs($this->user)
            ->get(route('routine-weeks.show', '2026-11-02'))
            ->assertDontSee("Create this week's sheet", false);
    }

    public function test_create_copies_the_previous_sheets_active_routines(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up']);
        $meditate = Routine::factory()->inactive()->create(['name' => 'Meditate']);
        Routine::factory()->create(['name' => 'Not on last sheet']);
        RoutineWeek::factory()->create(['week_start' => '2026-11-09'])->routines()->attach([$wake->id, $meditate->id]);

        $this->actingAs($this->user)
            ->post(route('routine-weeks.store'))
            ->assertRedirect(route('routine-weeks.show', '2026-11-16'))
            ->assertSessionHas('flash.message', "This week's sheet was created with 1 routines.");

        // Only the ACTIVE routine of last sheet is copied.
        $this->assertSame([$wake->id], $this->thisWeek()->routines->pluck('id')->all());
    }

    public function test_first_sheet_ever_gets_all_active_top_level_routines(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up']);
        $exercise = Routine::factory()->create(['name' => 'Exercise']);
        Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run']);
        Routine::factory()->inactive()->create(['name' => 'Meditate']);

        $this->actingAs($this->user)->post(route('routine-weeks.store'));

        $this->assertEqualsCanonicalizing(
            [$wake->id, $exercise->id],
            $this->thisWeek()->routines->pluck('id')->all(),
        );
    }

    public function test_creating_twice_keeps_one_sheet(): void
    {
        $this->actingAs($this->user)->post(route('routine-weeks.store'));
        $this->actingAs($this->user)->post(route('routine-weeks.store'))->assertRedirect();

        $this->assertSame(1, RoutineWeek::count());
    }

    // ── Edit page ───────────────────────────────────────────

    public function test_edit_page_lists_routines_with_the_sheets_ones_checked(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up']);
        Routine::factory()->create(['name' => 'Drink water']);
        $read = Routine::factory()->inactive()->create(['name' => 'Read']);          // inactive but on the sheet → still listed
        Routine::factory()->inactive()->create(['name' => 'Meditate']);              // inactive, not on the sheet → hidden
        RoutineWeek::factory()->current()->create()->routines()->attach([$wake->id, $read->id]);

        $this->actingAs($this->user)
            ->get(route('routine-weeks.edit', '2026-11-16'))
            ->assertOk()
            ->assertSee('Week 16 – 22 Nov 2026')
            ->assertSee('Wake up')
            ->assertSee('Drink water')
            ->assertSee('Read')
            ->assertDontSee('Meditate')
            ->assertViewHas('selectedIds', fn (array $ids) => collect($ids)->sort()->values()->all() === collect([$wake->id, $read->id])->sort()->values()->all());
    }

    public function test_old_week_cannot_be_edited(): void
    {
        RoutineWeek::factory()->create(['week_start' => '2026-11-09']);

        $this->actingAs($this->user)->get(route('routine-weeks.edit', '2026-11-09'))->assertForbidden();
        $this->actingAs($this->user)->patch(route('routine-weeks.update', '2026-11-09'))->assertForbidden();
    }

    public function test_week_without_a_sheet_cannot_be_edited(): void
    {
        $this->actingAs($this->user)->get(route('routine-weeks.edit', '2026-11-16'))->assertNotFound();
    }

    // ── Save (sync) ─────────────────────────────────────────

    public function test_save_adds_and_removes_routines_and_keeps_ticks(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up']);
        $read = Routine::factory()->create(['name' => 'Read']);
        $water = Routine::factory()->create(['name' => 'Drink water']);
        $week = RoutineWeek::factory()->current()->create();
        $week->routines()->attach([$wake->id, $read->id]);
        RoutineCheck::factory()->create(['routine_id' => $read->id, 'done_on' => '2026-11-16']);

        // Untick "Read", tick "Drink water".
        $this->actingAs($this->user)
            ->patch(route('routine-weeks.update', '2026-11-16'), ['routine_ids' => [$wake->id, $water->id]])
            ->assertRedirect(route('routine-weeks.show', '2026-11-16'))
            ->assertSessionHas('flash.message', 'Sheet updated.');

        $this->assertEqualsCanonicalizing([$wake->id, $water->id], $week->routines()->pluck('routines.id')->all());
        $this->assertDatabaseCount('routine_checks', 1);   // ticks belong to routine + date, not to the sheet
    }

    public function test_nothing_checked_empties_the_sheet(): void
    {
        $wake = Routine::factory()->create();
        $week = RoutineWeek::factory()->current()->create();
        $week->routines()->attach($wake->id);

        // Unchecked boxes are not sent at all → no routine_ids in the request.
        $this->actingAs($this->user)->patch(route('routine-weeks.update', '2026-11-16'));

        $this->assertSame(0, $week->routines()->count());
    }

    public function test_sub_tasks_and_unknown_ids_are_rejected(): void
    {
        $exercise = Routine::factory()->create();
        $run = Routine::factory()->subTaskOf($exercise)->create();
        RoutineWeek::factory()->current()->create();

        $this->actingAs($this->user)
            ->patch(route('routine-weeks.update', '2026-11-16'), ['routine_ids' => [$run->id, 999]])
            ->assertSessionHasErrors(['routine_ids.0', 'routine_ids.1']);

        $this->assertSame(0, $this->thisWeek()->routines()->count());
    }

    // ── Model helper ────────────────────────────────────────

    public function test_previous_sheet_is_the_newest_one_before_the_week(): void
    {
        RoutineWeek::factory()->create(['week_start' => '2026-10-26']);
        $newest = RoutineWeek::factory()->create(['week_start' => '2026-11-02']);   // week of 9 Nov was skipped
        RoutineWeek::factory()->current()->create();

        $this->assertTrue(RoutineWeek::previousBefore(Carbon::parse('2026-11-16'))->is($newest));
        $this->assertNull(RoutineWeek::previousBefore(Carbon::parse('2026-10-26')));
    }
}
