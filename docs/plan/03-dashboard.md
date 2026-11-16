# 3️⃣ Dashboard

**Status:** ⏳ Not started · **Branch:** `feature/dashboard` · **Depends on:** Daily Tasks + Daily Routine done

## Goal

A read-only overview of today: task progress, overdue work and routine progress, with quick links to the other two pages.

## User stories

- As a user, I see a greeting and today's date when I open the app.
- As a user, I see today's task counts and overdue count at a glance.
- As a user, I see a short list of today's tasks.
- As a user, I see my morning and evening routine progress.
- As a user, I can jump to add a task or open the routine page.

---

## UI

Page: `/dashboard` (replaces the current Breeze placeholder)

| Section | Content |
|---|---|
| Greeting | "Good morning/afternoon/evening", today's date |
| Task summary cards | Total today · Completed · Pending · Overdue |
| Today's tasks | Short list (title + status badge), link "View all" → Daily Tasks |
| Routine progress | Morning X / Y · Evening X / Y (progress bars), link → Daily Routine |
| Quick actions | + Add task · Open routine |

Editing never happens here — buttons link to the owning page.

---

## Data sources

The Dashboard **owns no tables** and has **no forms**.

| Widget | Reads from |
|---|---|
| Task summary, today's tasks, overdue | `daily_tasks` (same query scopes as the Daily Tasks page) |
| Routine progress | `routines` + `routine_completions` for today |

Reuse, don't duplicate: model query scopes (e.g. `dueOn($date)`, `overdue()`) and Blade components (`status-badge`, `stat-card`) built for the other pages.

---

## Route

| HTTP | URL | Method |
|---|---|---|
| GET | `/dashboard` | `DashboardController@index` (already behind `auth`) |

Only `index` — the dashboard is not a CRUD resource.

---

## Business rules

1. Counts use the same definitions as the Daily Tasks page (one source of truth: model scopes).
2. Overdue tasks are counted separately from today's tasks.
3. Greeting depends on the current time (before 12:00 morning, before 17:00 afternoon, else evening).

---

## Implementation checklist

| Step | Work | Status |
|---|---|---|
| A | `DashboardController@index` replaces the Breeze closure route | ⏳ |
| B | Greeting and date | ⏳ |
| C | Task summary cards and overdue count (reuse scopes and `stat-card`) | ⏳ |
| D | Today's tasks short list (reuse `status-badge`) | ⏳ |
| E | Routine progress | ⏳ |
| F | Quick links | ⏳ |
| G | Feature tests (counts are correct for sample data) | ⏳ |

---

## Acceptance criteria

- [ ] Shows correct counts for today's tasks and overdue tasks.
- [ ] Shows today's task list and routine progress.
- [ ] All links open the right page.
- [ ] No editing is possible from the dashboard.
- [ ] Feature tests pass in CI.

## Out of scope (v1)

Charts and history over time, weekly/monthly views, customisable widgets.
