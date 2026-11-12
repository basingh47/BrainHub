# 1️⃣ Daily Tasks

**Status:** 🚧 In progress · **Branch:** `feature/daily-tasks` · **Depends on:** nothing

## Goal

Create, organise, complete and reschedule one-off tasks, and keep completed tasks as history.

## User stories

- As a user, I can add a task with a title, due date and optional details.
- As a user, I can see the tasks for a selected date, filtered by status and category.
- As a user, I can see overdue tasks separately, with their original due date.
- As a user, I can mark a task completed, change its status, edit it or delete it.
- As a user, I can move an unfinished task to another date without creating a duplicate.

---

## UI

Page: `/daily-tasks` (design: PNG mockup)

| Section | Content |
|---|---|
| Header | "Daily Tasks", today's date, **+ Add task** button |
| Stat cards | Today · Pending · In progress · Completed · Overdue |
| Filter bar | Date · Status · Category · Filter · Reset |
| Overdue box | Overdue tasks with original due date · actions: Complete, Move |
| Today's tasks table | Task · Category · Priority · Due time · Status · actions: Complete, ••• (Edit, Move, Delete) |
| Move popup | New due date (today or later) |
| Add / Edit form | Title*, Description, Category, Priority, Due date*, Due time, Status (edit only) |

Completed rows are shown with strikethrough and "Completed at …", with no actions except via edit.

---

## Data model — `daily_tasks`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint | Primary key |
| `title` | string | Required |
| `description` | text, nullable | Optional |
| `category` | string, nullable | `office` · `learning` · `personal` |
| `priority` | string | `high` · `medium` · `low` — default `medium` |
| `due_date` | date | Required |
| `due_time` | time, nullable | Optional |
| `status` | string | `pending` · `in_progress` · `completed` — default `pending` |
| `completed_at` | timestamp, nullable | Set when status becomes `completed` |
| `created_at`, `updated_at` | timestamps | Laravel |

Indexes: `due_date`, `status`.

**Not stored:** "overdue" — it is computed (`due_date < today` and `status != completed`).

---

## Routes

`Route::resource('daily-tasks', DailyTaskController::class)` inside the `auth` group.

| Action in UI | HTTP | URL | Method |
|---|---|---|---|
| List + filters | GET | `/daily-tasks?date=&status=&category=` | `index` |
| Add form | GET | `/daily-tasks/create` | `create` |
| Save new | POST | `/daily-tasks` | `store` |
| Edit form | GET | `/daily-tasks/{task}/edit` | `edit` |
| Save edit | PUT | `/daily-tasks/{task}` | `update` |
| Complete | PATCH | `/daily-tasks/{task}` (`status=completed`) | `update` |
| Move (carry forward) | PATCH | `/daily-tasks/{task}` (`due_date`) | `update` |
| Delete | DELETE | `/daily-tasks/{task}` | `destroy` |

`show` is optional for v1.

---

## Business rules

1. A task not completed by its due date becomes **overdue** and keeps its original `due_date`.
2. **Carry forward is manual**: it updates the existing task's `due_date`. It never creates a copy.
3. The new due date must be **today or later**.
4. A **completed** task cannot be carried forward (enforced in validation, not only hidden in the UI).
5. Setting status to `completed` sets `completed_at`; changing it back clears `completed_at`.
6. Completed tasks are kept as history (no automatic deletion).
7. Delete asks for confirmation.

---

## Implementation checklist

| Step | Work | Status |
|---|---|---|
| A | Move `daily-tasks` route into the `auth` group | ✅ |
| B | Migration, `DailyTask` model, factory, seeder (enums not created yet, values are plain strings) | 🟡 |
| C | Task list with real data (`index`) — shows today's tasks and overdue tasks from the database | 🟡 (date filter from URL not applied yet) |
| D | `status-badge`, `priority-badge`, `task-row` components | ⏳ (badges are inline in `index.blade.php`) |
| E | Filters, stat cards, overdue box | 🟡 (stat cards and overdue box done; filter bar is UI only) |
| F | Complete and delete | ✅ (Complete via PATCH form, Delete via DELETE form with confirm) |
| G | ••• menu and Move popup (carry forward) | 🟡 (••• menu done; Move is a placeholder `href="#"`) |
| H | Create and edit forms with Form Requests | ✅ (`StoreDailyTaskRequest`, `UpdateDailyTaskRequest`) |
| I | Feature tests for every action and rule | ⏳ (no tests written yet) |

Done so far: resource controller with full CRUD, resource route inside `auth`, migration, model, factory, seeder, Form Requests, create and edit views, index page with real data, Complete and Delete actions, `completed_at` handling, delete confirmation.

Still to do: apply the filters in `index()`, add the Move action (with the "today or later" and "not completed" rules), create the enums and badge components, show a history view for completed tasks, and write feature tests.

---

## Acceptance criteria

- [ ] Guests are redirected to login; logged-in users see the page.
- [ ] Tasks for a selected date are listed and can be filtered by status and category.
- [x] A task can be added, edited and deleted; invalid input shows validation errors. (implemented, not yet tested)
- [x] A task's status can be changed and it can be marked completed (`completed_at` set). (implemented, not yet tested)
- [x] Overdue tasks are listed separately with their original due date.
- [ ] An unfinished task can be moved to a future date without creating a duplicate.
- [ ] A completed task cannot be moved.
- [ ] Completed tasks remain visible as history. (index shows today and overdue only)
- [ ] Feature tests cover the above and pass in CI.

## Out of scope (v1)

Multiple users / ownership, recurring tasks, reminders and notifications, attachments.
