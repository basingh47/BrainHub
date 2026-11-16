# 1️⃣ Daily Tasks

**Status:** 🚧 In progress (features done, quality work left) · **Branch:** `feature/daily-tasks` · **Depends on:** nothing

**Goal:** create, complete and reschedule one-off tasks; keep completed tasks as history.

---

## ✅ Done

- Database: `daily_tasks` migration, `DailyTask` model (fillable, casts, `markCompleted()` / `markIncomplete()`), factory, seeder
- List page: today's tasks (open first, completed last), overdue box, stat cards
- Add and Edit pages with Form Requests (`Store…`, `Update…`)
- Actions: Complete, Reopen, Move (popup), Delete
- UI: flash messages by type (`<x-flash>`), completed-row look, Daily Tasks in the navigation
- Routes behind `auth`; `show` removed; filters removed from scope

---

## ⏳ Remaining

### Must have (finish v1)

| # | Work | Why |
|---|---|---|
| 1 | **Feature tests** for every action and rule | nothing is tested yet; CI must prove the rules |
| 2 | **Enums** `TaskStatus`, `TaskPriority`, `TaskCategory` + model casts + `Rule::enum` | replace magic strings like `'completed'` |
| 3 | **Badge components** `<x-status-badge>`, `<x-priority-badge>` | badges are inline `@php` arrays in `index.blade.php` |
| 4 | `update()` uses `markCompleted()` / `markIncomplete()` | the `completed_at` rule should live only in the model |
| 5 | **Commit** in small commits → PR → CI green → merge | all work is still uncommitted |

### Should have (UI polish)

| # | Work |
|---|---|
| 6 | **Undo** button in the "completed" flash message (uses `reopen`) |
| 7 | "All done for today 🎉" message when every task today is completed |
| 8 | Delete confirmation as a popover (replace the browser `confirm()`) |
| 9 | Display fixes: due time `14:30` (no seconds), `—` for empty category, "3 days late" in the overdue box |

### Decide

| # | Question |
|---|---|
| 10 | **History of completed tasks**: build a small history view, or move it to *Out of scope*? (today the page shows only today + overdue) |

---

## Reference

### Routes (inside `auth`)

| Action | HTTP | URL | Method |
|---|---|---|---|
| List | GET | `/daily-tasks` | `index` |
| Add form / Save | GET / POST | `/daily-tasks/create` · `/daily-tasks` | `create` · `store` |
| Edit form / Save | GET / PUT | `/daily-tasks/{task}/edit` · `/daily-tasks/{task}` | `edit` · `update` |
| Complete | PATCH | `/daily-tasks/{task}/complete` | `complete` |
| Reopen | PATCH | `/daily-tasks/{task}/reopen` | `reopen` |
| Move | PATCH | `/daily-tasks/{task}/move` | `move` |
| Delete | DELETE | `/daily-tasks/{task}` | `destroy` |

Extra action routes are defined before `Route::resource(...)->except(['show'])`.

### Data model — `daily_tasks`

`title`* · `description` · `category` (office/learning/personal) · `priority` (high/medium/low, default medium) · `due_date`* · `due_time` · `status` (pending/in_progress/completed, default pending) · `completed_at` · timestamps. Indexes: `due_date`, `status`.

"Overdue" is computed (`due_date < today` and not completed), never stored.

### Business rules

1. An unfinished task past its due date is **overdue** and keeps its original `due_date`.
2. **Move** updates the same task's `due_date`; it never creates a copy.
3. The new due date must be **today or later**.
4. A **completed** task cannot be moved (blocked on the server, not only hidden).
5. Completing sets `completed_at`; reopening sets status `pending` and clears it.
6. Completed tasks are kept as history (no automatic deletion).
7. Delete asks for confirmation.

---

## Acceptance criteria

| Criterion | Built | Tested |
|---|---|---|
| Guests are redirected to login | ✅ | ⏳ |
| Today's tasks and overdue tasks are listed | ✅ | ⏳ |
| Add, edit, delete; invalid input shows errors | ✅ | ⏳ |
| Complete sets `completed_at`; Reopen clears it | ✅ | ⏳ |
| Move to today or later, no duplicate | ✅ | ⏳ |
| A completed task cannot be moved | ✅ | ⏳ |
| Completed tasks visible as history | ⏳ (see #10) | ⏳ |
| Feature tests pass in CI | — | ⏳ |

## Out of scope (v1)

Multiple users, recurring tasks, reminders, attachments, filters (date / status / category). Without a date filter the page shows only today and overdue; a task moved to a future date appears on that day.
