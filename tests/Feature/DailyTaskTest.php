<?php

namespace Tests\Feature;

use App\Models\DailyTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyTaskTest extends TestCase
{
    // Every test starts with an empty in-memory database (see phpunit.xml).
    use RefreshDatabase;

    private User $user;

    /**
     * Runs before EVERY test: create a logged-in user to act as.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ── Access ──────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        // Act: no actingAs() → we are a guest
        $response = $this->get(route('daily-tasks.index'));

        // Assert: the 'auth' middleware sends guests to the login page
        $response->assertRedirect(route('login'));
    }

    // ── List ────────────────────────────────────────────────

    public function test_list_shows_today_and_overdue_tasks_only(): void
    {
        DailyTask::factory()->create(['title' => 'Today task']);
        DailyTask::factory()->overdue()->create(['title' => 'Late task', 'status' => 'pending']);
        DailyTask::factory()->overdue()->completed()->create(['title' => 'Old finished task']);
        DailyTask::factory()->create(['title' => 'Future task', 'due_date' => today()->addDays(3)]);

        $response = $this->actingAs($this->user)->get(route('daily-tasks.index'));

        $response->assertOk();
        $response->assertSee('Today task');
        $response->assertSee('Late task');               // overdue box
        $response->assertDontSee('Old finished task');   // completed → not overdue
        $response->assertDontSee('Future task');         // not today
    }

    // ── Add ─────────────────────────────────────────────────

    public function test_create_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('daily-tasks.create'))
            ->assertOk();
    }

    public function test_user_can_add_a_task(): void
    {
        // Act: submit the Add form (POST /daily-tasks)
        $response = $this->actingAs($this->user)->post(route('daily-tasks.store'), [
            'title' => 'Write tests',
            'priority' => 'high',
            'due_date' => today()->toDateString(),
        ]);

        // Assert: redirect back to the list with a flash message, and the row exists
        $response->assertRedirect(route('daily-tasks.index'));
        $response->assertSessionHas('flash');
        $this->assertDatabaseHas('daily_tasks', [
            'title' => 'Write tests',
            'priority' => 'high',
            'status' => 'pending', // DB default — a new task always starts as pending
        ]);
    }

    public function test_title_is_required(): void
    {
        $response = $this->actingAs($this->user)->post(route('daily-tasks.store'), [
            'title' => '',
            'priority' => 'high',
            'due_date' => today()->toDateString(),
        ]);

        // Assert: validation error on 'title', and nothing was saved
        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('daily_tasks', 0);
    }

    // ── Edit ────────────────────────────────────────────────

    public function test_edit_page_shows_the_task(): void
    {
        $task = DailyTask::factory()->create(['title' => 'Edit me']);

        $this->actingAs($this->user)
            ->get(route('daily-tasks.edit', $task))
            ->assertOk()
            ->assertSee('Edit me');
    }

    public function test_edit_page_returns_404_for_a_missing_task(): void
    {
        $this->actingAs($this->user)
            ->get(route('daily-tasks.edit', 999))
            ->assertNotFound();
    }

    public function test_user_can_update_a_task(): void
    {
        $task = DailyTask::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->user)->put(route('daily-tasks.update', $task), [
            'title' => 'Updated title',
            'priority' => 'low',
            'due_date' => today()->toDateString(),
            'status' => 'in_progress',
        ]);

        $response->assertRedirect(route('daily-tasks.index'));
        $this->assertDatabaseHas('daily_tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'priority' => 'low',
            'status' => 'in_progress',
        ]);
    }

    public function test_updating_status_sets_and_clears_completed_at(): void
    {
        $task = DailyTask::factory()->create(['status' => 'pending']);
        $form = [
            'title' => $task->title,
            'priority' => 'medium',
            'due_date' => today()->toDateString(),
        ];

        // pending → completed: completed_at is set (plan rule 5)
        $this->actingAs($this->user)->put(route('daily-tasks.update', $task), $form + ['status' => 'completed']);
        $this->assertNotNull($task->fresh()->completed_at);

        // completed → pending: completed_at is cleared
        $this->actingAs($this->user)->put(route('daily-tasks.update', $task), $form + ['status' => 'pending']);
        $this->assertNull($task->fresh()->completed_at);
    }

    // ── Complete / Reopen ───────────────────────────────────

    public function test_user_can_complete_a_task(): void
    {
        $task = DailyTask::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->user)->patch(route('daily-tasks.complete', $task));

        $response->assertRedirect(route('daily-tasks.index'));
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_completing_twice_keeps_the_first_completed_at(): void
    {
        $firstTime = now()->subHour()->startOfSecond();
        $task = DailyTask::factory()->completed()->create(['completed_at' => $firstTime]);

        $this->actingAs($this->user)->patch(route('daily-tasks.complete', $task));

        // Idempotent: the original completion time is not overwritten
        $this->assertTrue($task->fresh()->completed_at->equalTo($firstTime));
    }

    public function test_user_can_reopen_a_completed_task(): void
    {
        $task = DailyTask::factory()->completed()->create();

        $response = $this->actingAs($this->user)->patch(route('daily-tasks.reopen', $task));

        $response->assertRedirect(route('daily-tasks.index'));
        $task->refresh();
        $this->assertSame('pending', $task->status);
        $this->assertNull($task->completed_at);
    }

    // ── Move ────────────────────────────────────────────────

    public function test_user_can_move_a_task_without_creating_a_copy(): void
    {
        $task = DailyTask::factory()->overdue()->create(['status' => 'pending']);

        $response = $this->actingAs($this->user)->patch(route('daily-tasks.move', $task), [
            'due_date' => today()->addDay()->toDateString(),
        ]);

        $response->assertRedirect(route('daily-tasks.index'));
        $this->assertTrue($task->fresh()->due_date->isSameDay(today()->addDay()));
        $this->assertDatabaseCount('daily_tasks', 1); // same task, no duplicate (plan rule 2)
    }

    public function test_task_cannot_be_moved_to_a_past_date(): void
    {
        $task = DailyTask::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->user)->patch(route('daily-tasks.move', $task), [
            'due_date' => today()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('due_date');                    // plan rule 3
        $this->assertTrue($task->fresh()->due_date->isSameDay(today()));  // unchanged
    }

    public function test_completed_task_cannot_be_moved(): void
    {
        $task = DailyTask::factory()->completed()->create();

        $response = $this->actingAs($this->user)->patch(route('daily-tasks.move', $task), [
            'due_date' => today()->addDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('due_date');                    // plan rule 4
        $this->assertTrue($task->fresh()->due_date->isSameDay(today()));  // unchanged
    }

    public function test_move_needs_patch_not_get(): void
    {
        $task = DailyTask::factory()->create();

        // A link (GET) to a PATCH-only route → 405 (the bug we hit with the ••• menu)
        $this->actingAs($this->user)
            ->get(route('daily-tasks.move', $task))
            ->assertStatus(405);
    }

    // ── Delete ──────────────────────────────────────────────

    public function test_user_can_delete_a_task(): void
    {
        $task = DailyTask::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('daily-tasks.destroy', $task));

        $response->assertRedirect(route('daily-tasks.index'));
        $this->assertDatabaseMissing('daily_tasks', ['id' => $task->id]);
    }
}
