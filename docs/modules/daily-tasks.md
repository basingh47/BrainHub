# Daily Tasks module — how it works

A map of the Daily Tasks code: pages, routes, controller, requests, model, table, test data and tests. It is the **simple** module: one table, one controller, one model. For the product plan (goals, UI, what is left) see [plan/01-daily-tasks.md](../plan/01-daily-tasks.md).

---

## The idea in one line

| Word | Meaning | Table |
|---|---|---|
| **Task** | One thing to do on one day (title, category, priority, due date/time, status). An unfinished task from an earlier day is **overdue** — computed, never stored. | `daily_tasks` |

---

## Pages (3)

| # | Page | URL | View | Controller method |
|---|---|---|---|---|
| 1 | **Task list** (today + overdue, stat cards) | `/daily-tasks` | `daily-tasks/index.blade.php` | `DailyTaskController@index` |
| 2 | **Add task** | `/daily-tasks/create` | `daily-tasks/create.blade.php` | `DailyTaskController@create` |
| 3 | **Edit task** | `/daily-tasks/{daily_task}/edit` | `daily-tasks/edit.blade.php` | `DailyTaskController@edit` |

Shared view pieces:

| File | Used by |
|---|---|
| `daily-tasks/layouts/daily-tasks-layout.blade.php` | Pages 1–3 — page header |
| `components/daily-tasks/complete-button.blade.php` | Page 1 — "Complete" form |
| `components/daily-tasks/reopen-button.blade.php` | Page 1 — "Reopen" form |
| `components/daily-tasks/move-popover.blade.php` | Page 1 — "Move" date picker form |
| `components/flash.blade.php` | Page 1 — success / info / danger message after a redirect |

### How the pages connect

```
Nav "Daily Tasks"
      │
      ▼
① Task list ── "Add task" ──▶ ② Add task ── Save ──▶ back to ①
      │
      ├── "Edit" ──▶ ③ Edit task ── Save ──▶ back to ①
      │
      └── Complete / Reopen / Move / Delete (forms, no page) ──▶ back to ① with a flash message
```

---

## Routes and actions

All routes are in `routes/web.php`, inside the `auth` middleware group.

| Action | Method + URL | Route name | Controller | Validation |
|---|---|---|---|---|
| List | `GET /daily-tasks` | `daily-tasks.index` | `index` | — |
| Add form | `GET /daily-tasks/create` | `daily-tasks.create` | `create` | — |
| Save new | `POST /daily-tasks` | `daily-tasks.store` | `store` | `StoreDailyTaskRequest` |
| Edit form | `GET /daily-tasks/{daily_task}/edit` | `daily-tasks.edit` | `edit` | — |
| Save edit | `PATCH /daily-tasks/{daily_task}` | `daily-tasks.update` | `update` | `UpdateDailyTaskRequest` |
| Complete | `PATCH /daily-tasks/{daily_task}/complete` | `daily-tasks.complete` | `complete` | — (no input) |
| Reopen | `PATCH /daily-tasks/{daily_task}/reopen` | `daily-tasks.reopen` | `reopen` | — (no input) |
| Move to another day | `PATCH /daily-tasks/{daily_task}/move` | `daily-tasks.move` | `move` | `MoveDailyTaskRequest` |
| Delete | `DELETE /daily-tasks/{daily_task}` | `daily-tasks.destroy` | `destroy` | — |

`Route::resource('daily-tasks', ...)->except(['show'])` makes the 7 resource routes; `complete`, `reopen`, `move` are written by hand **above** it.

Every save redirects back to the list with a flash message (`backToList()` — the PRG pattern, so F5 does not submit twice).

---

## Controller: `DailyTaskController`

Uses `string $id` + `DailyTask::findOrFail($id)` (not route model binding) → a missing task gives 404.

| Method | What it does |
|---|---|
| `index` | Today's tasks (open first, then by `due_time`) + overdue tasks (before today, not completed) + counts for the stat cards |
| `store` | `DailyTask::create($request->validated())` |
| `update` | Saves the form; sets `completed_at` when status becomes `completed`, clears it otherwise |
| `complete` / `reopen` | `markCompleted()` / `markIncomplete()` on the model |
| `move` | Changes `due_date` of the **same** task (never a copy) |
| `destroy` | Deletes the row |

---

## Form Requests (3)

| File | Rules |
|---|---|
| `StoreDailyTaskRequest` | `title` required ≤255 · `category` in office/learning/personal · `priority` in high/medium/low · `due_date` today or later · `due_time` `H:i`. No `status` — a new task is always `pending`. |
| `UpdateDailyTaskRequest` | Same fields, but `due_date` may be in the past (overdue tasks stay editable) and `status` is required (pending/in_progress/completed). |
| `MoveDailyTaskRequest` | `due_date` today or later · `after()`: a completed task cannot be moved. |

---

## Model: `DailyTask`

- `#[Fillable([...])]` (Laravel 13 attribute): `title`, `description`, `category`, `priority`, `due_date`, `due_time`, `status`, `completed_at`. **`user_id` is not in it — on purpose** (it must come from the logged-in user, never from the form).
- Casts: `due_date` → date, `completed_at` → datetime.
- `markCompleted()` — sets `completed` + `completed_at`; does nothing if already completed (keeps the first time).
- `markIncomplete()` — back to `pending`, clears `completed_at`.
- **No relationships yet** (no `user()`), and `User` has no `dailyTasks()`.

---

## Table: `daily_tasks`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `user_id` | FK → users | **required**, `ON DELETE CASCADE` (added 25 Nov 2026) |
| `title` | string | |
| `description` | text | nullable |
| `category` | string | nullable — office, learning, personal |
| `priority` | string | default `medium` |
| `due_date` | date | indexed |
| `due_time` | time | nullable |
| `status` | string | default `pending`, indexed |
| `completed_at` | timestamp | nullable |
| `created_at`, `updated_at` | timestamps | |

Migrations: `2026_11_06_094400_create_daily_tasks_table.php`, `2026_11_25_110653_add_user_id_to_daily_tasks_table.php`. Full columns of all tables: [database-design.md](../database-design.md).

---

## Business rules

| Rule | Where it is enforced |
|---|---|
| The list shows **today** + **overdue** only | `DailyTaskController@index` |
| Overdue = due before today **and** not completed (computed) | `DailyTaskController@index` |
| A new task cannot be due in the past | `StoreDailyTaskRequest` |
| A new task always starts as `pending` | DB default; `status` not accepted on store |
| Move = same task, new date (never a copy) | `DailyTaskController@move` |
| Move only to today or later; completed tasks cannot move | `MoveDailyTaskRequest` |
| `completed_at` is set once and kept if completed again | `DailyTask::markCompleted()`, `@update` |

---

## Test data

| File | What it does |
|---|---|
| `database/factories/DailyTaskFactory.php` | A task due today, `pending` or `in_progress`. States: `overdue()`, `completed()`. **No `user_id`.** |
| `database/seeders/DailyTaskSeeder.php` | **Empties** `daily_tasks`, then adds 10 tasks from the factory. |

```bash
docker compose exec app php artisan db:seed --class=DailyTaskSeeder   # empties daily_tasks, adds 10 tasks
```

Never run seeders in production.

---

## Tests: `tests/Feature/DailyTaskTest.php`

guest redirected · list shows today + overdue only · create page loads · add a task · title required · edit page · edit 404 · update · status sets/clears `completed_at` · complete · complete twice keeps first time · reopen · move without a copy · cannot move to the past · completed task cannot move · move needs PATCH · delete.

`setUp()` creates one user and every test uses `actingAs($this->user)` — but no test checks that **another** user's task is hidden or forbidden.

---

## User ownership: what must change

The `user_id` column was added **after** this module was built (25 Nov 2026). The table is ready; **none of the code uses it yet**. Today every logged-in user sees and changes every task, and saving a new task fails because `user_id` is required but never set.

| # | File | Now | Must become | Status |
|---|---|---|---|---|
| 1 | Migration `add_user_id_to_daily_tasks_table` | `user_id` required + FK + cascade | — | ✅ |
| 2 | `app/Models/DailyTask.php` | no `user()` | add `user(): BelongsTo` | ⏳ |
| 3 | `app/Models/User.php` | no `dailyTasks()` | add `dailyTasks(): HasMany` | ⏳ |
| 4 | `DailyTaskController@index` | `DailyTask::whereDate(...)` (all users) | `$request->user()->dailyTasks()->whereDate(...)` | ⏳ |
| 5 | `DailyTaskController@store` | `DailyTask::create(...)` → **fails** (no `user_id`) | `$request->user()->dailyTasks()->create(...)` | ⏳ |
| 6 | `edit` / `update` / `complete` / `reopen` / `move` / `destroy` | `DailyTask::findOrFail($id)` (any user's task) | only my task → 403 (Policy) or 404 (find through `dailyTasks()`) | ⏳ |
| 7 | `MoveDailyTaskRequest::after()` | `DailyTask::find(...)` (any task) | same lookup as the controller (only my task) | ⏳ |
| 8 | `DailyTaskFactory` | no `user_id` → **create fails** | `'user_id' => User::factory()` | ⏳ |
| 9 | `DailyTaskSeeder` | tasks have no owner | `->for($me)` (first user), like `RoutineOnlySeeder` | ⏳ |
| 10 | `DailyTaskTest` | creates tasks with no owner | create with `->for($this->user)`; add tests: another user's task is hidden from the list, and edit/update/move/delete of it is refused | ⏳ |
| 11 | API (`routes/api.php`) | only `GET /api/user` (Sanctum) | task endpoints, later — same ownership rules | ⏳ later |

Suggested order: 2–3 (relationships) → 8 (factory, so tests can run) → 4–5 → 6–7 → 9 → 10 → 11.

---

*Last updated: 26 November 2026*
