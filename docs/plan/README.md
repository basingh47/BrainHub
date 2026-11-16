# 🧭 BrainHub v1 — Product Plan

BrainHub is a personal productivity app: plan the day's tasks, track daily routines, and see progress at a glance.

Version 1 is limited to **three pages**. Each page has its own plan document.

| # | Page | Purpose | Plan | Status |
|---|---|---|---|---|
| 1 | Daily Tasks | Create, track, complete and reschedule one-off tasks | [01-daily-tasks.md](01-daily-tasks.md) | 🚧 In progress |
| 2 | Daily Routine | Track repeating morning/evening habits per day | [02-daily-routine.md](02-daily-routine.md) | ⏳ Not started |
| 3 | Dashboard | Read-only summary of today (tasks + routine) | [03-dashboard.md](03-dashboard.md) | ⏳ Not started |

---

## Build order

```text
Daily Tasks ──► Daily Routine ──► Dashboard
```

- A page starts only when the previous one meets its acceptance criteria.
- The Dashboard is last because it only **reads** data owned by the other two pages.

---

## Shared decisions (apply to every page)

| Area | Decision | Why |
|---|---|---|
| Stack | Laravel 12, PHP 8.4, MySQL, Blade, Tailwind CSS, Alpine.js | Tailwind + Alpine already installed by Breeze |
| Access | All pages behind the `auth` middleware | App is public on Render; Breeze login already exists |
| Ownership | **Single user** — no `user_id` columns in v1 | Keep v1 simple; multi-user is a later migration |
| Controllers | Resource controllers only (`Route::resource`) | Predictable REST structure; extra actions = `update` via PATCH |
| Validation | Form Request classes (`Store…Request`, `Update…Request`) | Keeps controllers thin |
| Fixed choices | PHP Enums (status, priority, category, period) | No magic strings, typo-safe |
| Views | `resources/views/<feature>/`, components in `components/<feature>/` | One folder per feature |
| Tests | Feature tests per resource in `tests/Feature/<Feature>/` | Every page ships with tests |

---

## Navigation

```text
BrainHub   Daily Tasks · Daily Routine · Dashboard          [user menu ▾]
```

Add/edit forms belong to their page; they are not separate menu items.

---

## Data model overview

| Table | Owned by | Purpose |
|---|---|---|
| `users` | Breeze | Login accounts |
| `daily_tasks` | Daily Tasks | One-off tasks and their status |
| `routines` | Daily Routine | Reusable routine items (e.g. "Exercise") |
| `routine_completions` | Daily Routine | One row per routine item completed on a date |

The Dashboard owns **no tables**.

---

## Delivery workflow

- One feature branch per page: `feature/daily-tasks`, `feature/daily-routine`, `feature/dashboard`
- One commit per checklist step; open the PR as **Draft**, mark **Ready for review** when acceptance criteria pass
- PR into `develop` → CI green → merge → release `develop` → `main`

See [git-workflow/pull-requests.md](../git-workflow/pull-requests.md).

---

## v1 is done when

- [ ] All three pages meet their acceptance criteria
- [ ] Feature tests pass in CI
- [ ] Pages work on mobile and desktop widths
- [ ] Deployed on Render and verified live
