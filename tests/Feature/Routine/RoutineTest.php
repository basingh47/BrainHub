<?php

namespace Tests\Feature\Routine;

use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manage routines: /routines (list + add form), edit, activate / deactivate.
 */
class RoutineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ── List page ───────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('routines.index'))->assertRedirect(route('login'));
    }

    public function test_list_shows_routines_and_their_sub_tasks(): void
    {
        $exercise = Routine::factory()->create(['name' => 'Exercise']);
        Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run']);
        Routine::factory()->inactive()->create(['name' => 'Meditate']);

        $this->actingAs($this->user)
            ->get(route('routines.index'))
            ->assertOk()
            ->assertSee('Exercise')
            ->assertSee('2 km run')
            ->assertSee('Meditate')
            ->assertSee('Inactive');
    }

    // ── Add ─────────────────────────────────────────────────

    public function test_user_can_add_a_routine(): void
    {
        $this->actingAs($this->user)
            ->post(route('routines.store'), ['name' => 'Drink 2 L water', 'target_time' => '08:00'])
            ->assertRedirect(route('routines.index'));

        $this->assertDatabaseHas('routines', ['name' => 'Drink 2 L water', 'parent_id' => null]);
    }

    public function test_user_can_add_a_sub_task(): void
    {
        $exercise = Routine::factory()->create(['name' => 'Exercise']);

        $this->actingAs($this->user)
            ->post(route('routines.store'), ['name' => 'Stretching', 'parent_id' => $exercise->id]);

        $this->assertTrue(Routine::where('name', 'Stretching')->first()->parent->is($exercise));
    }

    // ── Edit ────────────────────────────────────────────────

    public function test_edit_page_shows_the_routine(): void
    {
        $exercise = Routine::factory()->create(['name' => 'Exercise', 'target_time' => '06:30']);
        $run = Routine::factory()->subTaskOf($exercise)->create(['name' => '2 km run']);

        $this->actingAs($this->user)
            ->get(route('routines.edit', $exercise))
            ->assertOk()
            ->assertSee('Exercise')
            ->assertSee('06:30')
            ->assertSee('Top-level routine');

        $this->actingAs($this->user)
            ->get(route('routines.edit', $run))
            ->assertOk()
            ->assertSee('Sub-task of Exercise');
    }

    public function test_user_can_update_a_routine(): void
    {
        $routine = Routine::factory()->create(['name' => 'Read', 'target_time' => '21:00']);

        $this->actingAs($this->user)
            ->patch(route('routines.update', $routine), ['name' => 'Read 20 min', 'target_time' => '21:30'])
            ->assertRedirect(route('routines.index'));

        $routine->refresh();
        $this->assertSame('Read 20 min', $routine->name);
        $this->assertStringStartsWith('21:30', $routine->target_time);
    }

    public function test_unknown_routine_returns_404(): void
    {
        $this->actingAs($this->user)->get('/routines/999/edit')->assertNotFound();
    }

    // ── Deactivate / activate (archive instead of delete) ───

    public function test_user_can_deactivate_a_routine(): void
    {
        $routine = Routine::factory()->create(['name' => 'Meditate']);

        $this->actingAs($this->user)
            ->patch(route('routines.deactivate', $routine))
            ->assertRedirect(route('routines.index'))
            ->assertSessionHas('flash.message', '"Meditate" deactivated.');

        $this->assertFalse($routine->refresh()->is_active);
    }

    public function test_user_can_activate_a_routine(): void
    {
        $routine = Routine::factory()->inactive()->create(['name' => 'Meditate']);

        $this->actingAs($this->user)
            ->patch(route('routines.activate', $routine))
            ->assertSessionHas('flash.message', '"Meditate" activated.');

        $this->assertTrue($routine->refresh()->is_active);
    }
}
