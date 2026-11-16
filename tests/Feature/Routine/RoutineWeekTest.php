<?php

namespace Tests\Feature\Routine;

use App\Models\Routine;
use App\Models\RoutineCheck;
use App\Models\RoutineWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The weekly sheet page: /daily-routine and /routine-weeks/{date}.
 *
 * "Today" is frozen on Wednesday 18 Nov 2026, so the week 16–22 Nov has
 * past days (Mon, Tue), today (Wed) and future days (Thu–Sun).
 */
class RoutineWeekTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-18 10:00'));
        $this->user = User::factory()->create();
    }

    // ── Access and URLs ─────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('daily-routine'))->assertRedirect(route('login'));
    }

    public function test_menu_link_opens_this_weeks_sheet(): void
    {
        $this->actingAs($this->user)
            ->get(route('daily-routine'))
            ->assertRedirect(route('routine-weeks.show', '2026-11-16'));
    }

    public function test_any_date_redirects_to_the_monday_of_its_week(): void
    {
        $this->actingAs($this->user)
            ->get(route('routine-weeks.show', '2026-11-19'))
            ->assertRedirect(route('routine-weeks.show', '2026-11-16'));
    }

    public function test_impossible_date_returns_404(): void
    {
        $this->actingAs($this->user)->get('/routine-weeks/2026-13-45')->assertNotFound();
    }

    public function test_week_without_a_sheet_shows_an_empty_state(): void
    {
        $this->actingAs($this->user)
            ->get(route('routine-weeks.show', '2026-11-16'))
            ->assertOk()
            ->assertSee('No sheet for this week');
    }

    public function test_sheet_without_routines_says_so(): void
    {
        RoutineWeek::factory()->current()->create();

        $this->actingAs($this->user)
            ->get(route('routine-weeks.show', '2026-11-16'))
            ->assertOk()
            ->assertSee('This sheet has no routines yet.');
    }

    // ── The grid ────────────────────────────────────────────

    public function test_sheet_shows_routines_sub_tasks_and_their_status_per_day(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up', 'target_time' => '06:00']);
        $exercise = Routine::factory()->create(['name' => 'Exercise', 'target_time' => '06:30']);
        $pushUps = Routine::factory()->subTaskOf($exercise)->create(['name' => '20 push-ups', 'sort_order' => 1]);
        $run = Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run', 'sort_order' => 2]);
        $stretch = Routine::factory()->subTaskOf($exercise)->create(['name' => 'Stretching', 'sort_order' => 3]);
        RoutineWeek::factory()->current()->create()->routines()->attach([$wake->id, $exercise->id]);

        // Monday: all 3 sub-tasks → Exercise done. Tuesday: 1 of 3 → partly done. Wednesday: none.
        foreach ([$wake, $pushUps, $run, $stretch] as $routine) {
            RoutineCheck::factory()->create(['routine_id' => $routine->id, 'done_on' => '2026-11-16']);
        }
        RoutineCheck::factory()->create(['routine_id' => $pushUps->id, 'done_on' => '2026-11-17']);

        $response = $this->actingAs($this->user)->get(route('routine-weeks.show', '2026-11-16'));

        $response->assertOk()
            ->assertSee('Week 16 – 22 Nov 2026')
            ->assertSeeInOrder(['Wake up', 'Exercise', '20 push-ups', '2 km run', 'Stretching'])
            ->assertSee('1/3');

        $response->assertViewHas('rows', function (array $rows) {
            $exercise = collect($rows)->firstWhere('name', 'Exercise')['cells'];
            $wake = collect($rows)->firstWhere('name', 'Wake up')['cells'];

            return $exercise[0]['status'] === 'done'       // Mon: 3/3
                && $exercise[1]['status'] === 'partial'    // Tue: 1/3
                && $exercise[1]['progress'] === '1/3'
                && $exercise[2]['status'] === 'parent'     // Wed: 0/3
                && $exercise[3]['status'] === 'locked'     // Thu: future
                && $wake[0]['status'] === 'done'
                && $wake[1]['status'] === 'open'
                && $wake[0]['check_id'] !== null;
        });

        // "Done per day": Monday both routines done, Tuesday none, future days empty.
        $response->assertViewHas('totals', fn (Collection $totals) => $totals[0] === '2 / 2' && $totals[1] === '0 / 2' && $totals[3] === null);
    }

    public function test_only_this_weeks_past_days_and_today_can_be_ticked(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up']);
        $exercise = Routine::factory()->create(['name' => 'Exercise']);
        Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run']);
        RoutineWeek::factory()->current()->create()->routines()->attach([$wake->id, $exercise->id]);

        $response = $this->actingAs($this->user)->get(route('routine-weeks.show', '2026-11-16'));

        $response->assertViewHas('rows', function (array $rows) {
            $wake = collect($rows)->firstWhere('name', 'Wake up')['cells'];
            $exercise = collect($rows)->firstWhere('name', 'Exercise')['cells'];

            return $wake[0]['editable'] && $wake[2]['editable']   // Mon, Wed (today)
                && ! $wake[3]['editable']                         // Thu (future)
                && ! $exercise[0]['editable'];                    // a parent is never ticked directly
        });
        $response->assertSee('click to tick');
    }

    public function test_old_week_is_read_only(): void
    {
        $wake = Routine::factory()->create(['name' => 'Wake up']);
        $lastWeek = RoutineWeek::factory()->create(['week_start' => '2026-11-09']);
        $lastWeek->routines()->attach($wake->id);
        RoutineCheck::factory()->create(['routine_id' => $wake->id, 'done_on' => '2026-11-10']);

        $response = $this->actingAs($this->user)->get(route('routine-weeks.show', '2026-11-09'));

        $response->assertOk()
            ->assertSee('Week 9 – 15 Nov 2026')
            ->assertDontSee('click to tick')
            ->assertDontSee('click to untick')
            ->assertSee(route('daily-routine'));  // "This week" link
        $response->assertViewHas('rows', fn (array $rows) => collect($rows[0]['cells'])->every(fn (array $cell) => ! $cell['editable']));
    }

    public function test_week_across_two_months_shows_both_months(): void
    {
        $this->actingAs($this->user)
            ->get(route('routine-weeks.show', '2026-11-30'))
            ->assertOk()
            ->assertSee('Week 30 Nov – 6 Dec 2026');
    }

    // ── Relationships ───────────────────────────────────────

    public function test_relationships_connect_routines_sub_tasks_sheets_and_ticks(): void
    {
        $exercise = Routine::factory()->create(['name' => 'Exercise']);
        $run = Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run']);
        $sheet = RoutineWeek::factory()->current()->create();
        $sheet->routines()->attach($exercise->id);
        $check = RoutineCheck::factory()->create(['routine_id' => $run->id, 'done_on' => '2026-11-16']);

        $this->assertTrue($run->parent->is($exercise));
        $this->assertTrue($exercise->children->first()->is($run));
        $this->assertTrue($exercise->weeks->first()->is($sheet));
        $this->assertTrue($sheet->routines->first()->is($exercise));
        $this->assertTrue($check->routine->is($run));
        $this->assertTrue($run->checks->first()->is($check));
        $this->assertTrue(RoutineWeek::forDate(Carbon::parse('2026-11-19'))->is($sheet));
    }
}
