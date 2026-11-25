# Daily Routine module — how it works

A map of the Daily Routine code: pages, routes, controllers, models, tables, and the rules that connect them. For the product plan (goals, user stories, what is left) see [plan/02-daily-routine.md](../plan/02-daily-routine.md).

---

## The idea in one line each

| Word | Meaning | Table |
|---|---|---|
| **Routine** | *What* habit I have (e.g. Exercise). Can have **sub-tasks** (Exercise → Push-ups, Run 2 km). | `routines` |
| **Week (sheet)** | *Which* routines I track this week. One sheet per user per week, identified by its **Monday**. | `routine_weeks` + `routine_week_items` |
| **Check (tick)** | *When* I did it: one row = one routine done on one day. | `routine_checks` |

---

## Pages (4)

| # | Page | URL | View | Controller method |
|---|---|---|---|---|
| 1 | **Weekly sheet** (tick boxes) | `/daily-routine` → `/routine-weeks/{monday}` | `routine-weeks/show.blade.php` | `RoutineWeekController@current` → `@show` |
| 2 | **Edit sheet** (which routines are on this week) | `/routine-weeks/{monday}/edit` | `routine-weeks/edit.blade.php` | `RoutineWeekController@edit` |
| 3 | **Manage routines** (list + Add form) | `/routines` | `routines/index.blade.php` | `RoutineController@index` |
| 4 | **Edit routine** | `/routines/{routine}/edit` | `routines/edit.blade.php` | `RoutineController@edit` |

Shared view pieces:

| File | Used by |
|---|---|
| `routine-weeks/layouts/routine-weeks-layout.blade.php` | Pages 1, 2 — header with "Edit sheet" and "Manage routines" |
| `routines/layouts/routine-layout.blade.php` | Pages 3, 4 — header with "← Back to this week" |
| `components/routines/check-cell.blade.php` | Page 1 — one box: tick form, untick form, or display only |

### How the pages connect

```
Nav "Daily Routine"
      │
      ▼
① Weekly sheet ──── "Edit sheet" ─────▶ ② Edit sheet ── Save ──▶ back to ①
      │  tick / untick (forms, no page)
      │
      └──── "Manage routines" ─────▶ ③ Manage routines ── "Edit" ──▶ ④ Edit routine
                                          │  Add / Activate / Deactivate (forms)
                                          └── "← Back to this week" ──▶ ①
```

---

## Actions (forms that save, then redirect)

| Action | Method + URL | Controller |
|---|---|---|
| Create this week's sheet | `POST /routine-weeks` | `RoutineWeekController@store` |
| Save edit sheet | `PATCH /routine-weeks/{monday}` | `RoutineWeekController@update` |
| Tick a box | `POST /routine-checks` | `RoutineCheckController@store` |
| Untick a box | `DELETE /routine-checks/{id}` | `RoutineCheckController@destroy` |
| Add routine | `POST /routines` | `RoutineController@store` |
| Save edit routine | `PATCH /routines/{routine}` | `RoutineController@update` |
| Activate / Deactivate | `PATCH /routines/{routine}/activate` · `/deactivate` | `RoutineController@activate` · `@deactivate` |

All routes are in `routes/web.php`, inside the `auth` middleware group. `{monday}` must match `\d{4}-\d{2}-\d{2}`.

---

## Controllers (3) — one job each

| Controller | Job | Main table |
|---|---|---|
| `RoutineController` | The list of habits: add, edit, activate, deactivate | `routines` |
| `RoutineWeekController` | The weekly sheet: show, create, choose routines | `routine_weeks`, `routine_week_items` |
| `RoutineCheckController` | Ticks: create or delete one check | `routine_checks` |

Validation for ticks lives in `app/Http/Requests/StoreRoutineCheckRequest.php`.

---

## Models and relationships

| Model | Relationship | Meaning |
|---|---|---|
| `Routine` | `parent()` belongsTo Routine | a sub-task's parent |
| | `children()` hasMany Routine (ordered by `sort_order`) | a routine's sub-tasks |
| | `checks()` hasMany RoutineCheck | its ticks |
| | `weeks()` belongsToMany RoutineWeek (pivot `routine_week_items`) | sheets it is on |
| | `user()` belongsTo User | owner |
| `RoutineWeek` | `routines()` belongsToMany Routine | routines on this sheet |
| `RoutineCheck` | `routine()` belongsTo Routine | the ticked routine |
| `User` | `routines()` hasMany Routine | my routines |

Useful model methods: `RoutineWeek::forDate()`, `RoutineWeek::previousBefore()`, `RoutineWeek::isEditableDay()`, `Routine::statusOn($day)`, `Routine::doneChildrenOn($day)`.

---

## Tables

```
users
 ├── routines (user_id, parent_id → routines)     habits + sub-tasks
 │     ├── routine_checks (routine_id)            one tick = routine + date
 │     └── routine_week_items (routine_id) ┐
 └── routine_weeks (user_id)               │      one sheet per user per week
       └── routine_week_items (routine_week_id) ┘ pivot: which routines on which sheet
```

- `routine_checks` and `routine_week_items` have **no** `user_id`: they are owned through their routine / week.
- Unique rules: `routine_weeks (user_id, week_start)`, `routine_checks (routine_id, done_on)`.
- Every foreign key uses `ON DELETE CASCADE`: deleting a user removes all their routines, sheets, and ticks.

Full columns: [database-design.md](../database-design.md).

---

## Business rules

| Rule | Where it is enforced |
|---|---|
| A week starts on **Monday**; any date in the URL is redirected to its Monday | `RoutineWeekController@show` |
| Only **this week** can get a new sheet; old weeks are history | `RoutineWeekController@store` (uses today's Monday, no date sent) |
| A new sheet copies the **previous sheet's active** routines (or all active top-level routines if there is none) | `RoutineWeekController@store` |
| Only this week's sheet can be edited (403 otherwise) | `RoutineWeekController@edit` / `@update` |
| Tick only **this week, up to today** (no future days, old weeks read-only) | `StoreRoutineCheckRequest` rules, `RoutineWeek::isEditableDay()` on untick |
| A routine **with sub-tasks** is never ticked directly — it is done when **all** sub-tasks are done | `StoreRoutineCheckRequest::after()`, `Routine::statusOn()` |
| A routine must be **on that week's sheet** to be ticked | `StoreRoutineCheckRequest::after()` |
| Once per routine per day | `StoreRoutineCheckRequest::after()` + unique index |
| Routines are **deactivated, not deleted**, so old sheets keep their history | `RoutineController@deactivate` |

---

## Box states on the weekly sheet

| State | Looks like | Meaning |
|---|---|---|
| `done` | green ✓ | ticked (or, for a parent, all sub-tasks ticked) |
| `partial` | amber `1/3` | parent with some sub-tasks ticked |
| `open` | empty box | not done (clickable if the day is editable) |
| `parent` | empty box, not clickable | parent with no sub-task ticked |
| locked | dashed box | future day or old week |

---

## Per-user data (in progress)

Each user must see and change only their own routines. Tables are done (`user_id` + foreign keys). Code status:

| Place | Only my data? |
|---|---|
| Manage routines — list (`RoutineController@index`) | ✅ `$request->user()->routines()` |
| Add routine (`store`) | ⏳ must create through `$request->user()->routines()->create()` |
| Edit / update / activate / deactivate | ⏳ must return 403 for someone else's routine |
| Weekly sheet, create / edit sheet (`RoutineWeekController`) | ⏳ `forDate()` / `previousBefore()` must look only at my sheets |
| Tick / untick (`RoutineCheckController`, `StoreRoutineCheckRequest`) | ⏳ the routine must be mine |

---

## Local test data

```bash
docker compose exec app php artisan db:seed --class=RoutineOnlySeeder   # adds 10 routines for the first user
docker compose exec app php artisan db:seed --class=RoutineSeeder       # empties routine tables, adds routines + sheets + ticks
```

Never run seeders in production.

---

## Tests

`tests/Feature/Routine/`: `RoutineTest` (manage routines), `RoutineWeekTest` and `RoutineWeekSheetTest` (sheet, create / edit), `RoutineCheckTest` (tick / untick).

---

*Last updated: 25 November 2026*
