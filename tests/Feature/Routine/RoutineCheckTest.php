<?php

namespace Tests\Feature\Routine;

use App\Models\Routine;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tick (POST /routine-checks) and untick (DELETE /routine-checks/{id}).
 *
 * "Today" is frozen on Wednesday 18 Nov 2026 (week 16–22 Nov).
 */
class RoutineCheckTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Routine $wake;

    private Routine $exercise;

    private Routine $run;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-18 10:00'));
        $this->user = User::factory()->create();

        // This week's sheet: "Wake up" and "Exercise" (with one sub-task "2 km run").
        $this->wake = Routine::factory()->create(['name' => 'Wake up']);
        $this->exercise = Routine::factory()->create(['name' => 'Exercise']);
        $this->run = Routine::factory()->subTaskOf($this->exercise)->create(['name' => '2 km run']);
        RoutineWeek::factory()->current()->create()->routines()->attach([$this->wake->id, $this->exercise->id]);
    }

    private function tick(Routine $routine, string $date): TestResponse
    {
        return $this->actingAs($this->user)->post(route('routine-checks.store'), [
            'routine_id' => $routine->id,
            'done_on' => $date,
        ]);
    }

    // ── Tick ────────────────────────────────────────────────

    public function test_guest_cannot_tick(): void
    {
        $this->post(route('routine-checks.store'), ['routine_id' => $this->wake->id, 'done_on' => '2026-11-18'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('routine_checks', 0);
    }

    public function test_user_can_tick_today(): void
    {
        $this->tick($this->wake, '2026-11-18')->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseCount('routine_checks', 1);
        $this->assertTrue(RoutineCheck::first()->routine->is($this->wake));
    }

    public function test_user_can_tick_an_earlier_day_of_this_week(): void
    {
        $this->tick($this->wake, '2026-11-16')->assertSessionHasNoErrors();

        $this->assertTrue($this->wake->checks()->first()->done_on->isSameDay('2026-11-16'));
    }

    public function test_sub_task_can_be_ticked_when_its_parent_is_on_the_sheet(): void
    {
        $this->tick($this->run, '2026-11-18')->assertSessionHasNoErrors();

        $this->assertDatabaseCount('routine_checks', 1);
    }

    public function test_future_day_cannot_be_ticked(): void
    {
        $this->tick($this->wake, '2026-11-19')->assertSessionHasErrors(['done_on' => 'You cannot tick a future day.']);

        $this->assertDatabaseCount('routine_checks', 0);
    }

    public function test_old_week_cannot_be_ticked(): void
    {
        $this->tick($this->wake, '2026-11-13')->assertSessionHasErrors(['done_on' => 'Old weeks are read-only.']);

        $this->assertDatabaseCount('routine_checks', 0);
    }

    public function test_routine_with_sub_tasks_cannot_be_ticked_directly(): void
    {
        $this->tick($this->exercise, '2026-11-18')->assertSessionHasErrors(['routine_id' => 'Tick its sub-tasks instead.']);

        $this->assertDatabaseCount('routine_checks', 0);
    }

    public function test_routine_not_on_the_sheet_cannot_be_ticked(): void
    {
        $water = Routine::factory()->create(['name' => 'Drink water']);

        $this->tick($water, '2026-11-18')->assertSessionHasErrors(['routine_id' => "This routine is not on this week's sheet."]);
    }

    public function test_nothing_can_be_ticked_when_the_week_has_no_sheet(): void
    {
        RoutineWeek::query()->delete();

        $this->tick($this->wake, '2026-11-18')->assertSessionHasErrors('routine_id');
    }

    public function test_same_routine_cannot_be_ticked_twice_on_one_day(): void
    {
        $this->tick($this->wake, '2026-11-18');

        $this->tick($this->wake, '2026-11-18')->assertSessionHasErrors(['done_on' => 'Already ticked for this day.']);
        $this->assertDatabaseCount('routine_checks', 1);
    }

    public function test_unknown_routine_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('routine-checks.store'), ['routine_id' => 999, 'done_on' => '2026-11-18'])
            ->assertSessionHasErrors('routine_id');
    }

    // ── Untick ──────────────────────────────────────────────

    public function test_user_can_untick_a_day_of_this_week(): void
    {
        $check = RoutineCheck::factory()->create(['routine_id' => $this->wake->id, 'done_on' => '2026-11-17']);

        $this->actingAs($this->user)
            ->delete(route('routine-checks.destroy', $check))
            ->assertRedirect();

        $this->assertDatabaseMissing('routine_checks', ['id' => $check->id]);
    }

    public function test_old_week_cannot_be_unticked(): void
    {
        $check = RoutineCheck::factory()->create(['routine_id' => $this->wake->id, 'done_on' => '2026-11-12']);

        $this->actingAs($this->user)
            ->delete(route('routine-checks.destroy', $check))
            ->assertForbidden();

        $this->assertDatabaseHas('routine_checks', ['id' => $check->id]);
    }
}
