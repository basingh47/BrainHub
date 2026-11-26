# Daily Routine module — how it works

A map of the Daily Routine code. It is the **complex** module, so it is split into **3 sub-modules**, each with its own table(s), controller, routes, views, test data and tests. For the product plan (goals, user stories, what is left) see [plan/02-daily-routine.md](../plan/02-daily-routine.md). The simple module is [daily-tasks.md](daily-tasks.md).

---

## The 3 sub-modules

| # | Sub-module | Question it answers | Table(s) | Controller |
|---|---|---|---|---|
| 1 | **Routines** | *What* habits do I have? (Exercise → Push-ups, Run 2 km) | `routines` | `RoutineController` |
| 2 | **Weekly sheet** | *Which* routines do I track this week? One sheet per user per week, named by its **Monday**. | `routine_weeks`, `routine_week_items` | `RoutineWeekController` |
| 3 | **Checks** (ticks) | *When* did I do it? One row = one routine done on one day. | `routine_checks` | `RoutineCheckController` |

They depend on each other in one direction: **Routines ← Weekly sheet ← Checks**. A sheet lists routines; a tick needs a routine that is on that week's sheet.

### How the pages connect

```
Nav "Daily Routine"
      │
      ▼
② Weekly sheet ──── "Edit sheet" ─────▶ ② Edit sheet ── Save ──▶ back to the sheet
      │  ③ tick / untick (forms, no page)
      │
      └──── "Manage routines" ─────▶ ① Manage routines ── "Edit" ──▶ ① Edit routine
                                          │  Add / Activate / Deactivate (forms)
                                          └── "← Back to this week" ──▶ ② Weekly sheet
```

### Tables

```
users
 ├── routines (user_id, parent_id → routines)     ① habits + sub-tasks
 │     ├── routine_checks (routine_id)            ③ one tick = routine + date
 │     └── routine_week_items (routine_id) ┐
 └── routine_weeks (user_id)               │      ② one sheet per user per week
       └── routine_week_items (routine_week_id) ┘ ② pivot: which routines on which sheet
```

- `routine_checks` and `routine_week_items` have **no** `user_id`: they are owned through their routine / week.
- Every foreign key uses `ON DELETE CASCADE`: deleting a user removes all their routines, sheets and ticks.
- Full columns: [database-design.md](../database-design.md).

---

## ① Routines (Manage routines)

The list of habits and their sub-tasks. Routines are **deactivated, not deleted**, so old sheets keep their history.

### Pages

| Page | URL | View | Controller method |
|---|---|---|---|
| **Manage routines** (table + "Add routine" form) | `/routines` | `routines/index.blade.php` | `RoutineController@index` |
| **Edit routine** | `/routines/{routine}/edit` | `routines/edit.blade.php` | `RoutineController@edit` |

Layout: `routines/layouts/routine-layout.blade.php` — header with "← Back to this week".

### Routes (`routes/web.php`, `auth` group)

| Action | Method + URL | Route name | Controller |
|---|---|---|---|
| List | `GET /routines` | `routines.index` | `index` |
| Add | `POST /routines` | `routines.store` | `store` |
| Edit form | `GET /routines/{routine}/edit` | `routines.edit` | `edit` |
| Save edit | `PATCH /routines/{routine}` | `routines.update` | `update` |
| Remove | `DELETE /routines/{routine}` | `routines.destroy` | ⚠️ **no `destroy()` method yet** |
| Deactivate | `PATCH /routines/{routine}/deactivate` | `routines.deactivate` | `deactivate` |
| Activate | `PATCH /routines/{routine}/activate` | `routines.activate` | `activate` |

`Route::resource('routines', ...)->except(['show', 'create'])` makes 5 routes (no `create` page: the form is on the index page). Activate / deactivate are written by hand above it.

### Controller: `RoutineController`

| Method | What it does |
|---|---|
| `index` | My top-level routines with their `children` (sub-tasks) + `$parents` (active ones, for the "Part of" dropdown) |
| `store` | `Routine::create($request->all())` |
| `edit` | Shows the edit form (route model binding: unknown id → 404) |
| `update` | `$routine->update($request->all())` |
| `deactivate` / `activate` | Sets `is_active` false / true |

### Form Request

**None yet.** `store` and `update` take a plain `Request` and save `$request->all()` with no validation.

### Model: `Routine`

- `#[Fillable]`: `parent_id`, `name`, `target_time`, `sort_order`, `is_active` (no `user_id` — on purpose).
- Relationships: `parent()`, `children()` (ordered by `sort_order`), `checks()`, `weeks()`, `user()`.
- `statusOn($day)`, `isDoneOn($day)`, `doneChildrenOn($day)` — used by the weekly sheet ②, not by this page.

### Table: `routines`

`id`, `user_id` (required), `parent_id` (nullable, FK → `routines`), `name`, `target_time` (nullable), `sort_order` (default 0), `is_active` (default true), timestamps. Index `(is_active, parent_id)`.

### Test data

| File | What it does |
|---|---|
| `RoutineFactory` | Active top-level routine, random name/time, owner `User::factory()`. States: `subTaskOf($parent)` (same owner as parent), `inactive()`. |
| `RoutineOnlySeeder` | **Adds** (does not empty) 10 routines for the **first user**: Exercise + 3 sub-tasks, 5 random, 1 inactive Meditate. Running it twice gives duplicates. |
| `RoutineSeeder` | Seeds all 3 sub-modules — see [Local test data](#local-test-data). |

### Tests: `tests/Feature/Routine/RoutineTest.php`

guest redirected · list shows routines + sub-tasks · add a routine · add a sub-task · edit page · update · unknown routine 404 · deactivate · activate.

### Known issues

| Issue | Effect |
|---|---|
| `store` does not set `user_id` | **"Save routine" fails** (`user_id` is required) |
| No Form Request | name / `parent_id` / `target_time` are never checked; `parent_id` could point to another user's routine or to a sub-task |
| No owner check on `edit` / `update` / `activate` / `deactivate` | any logged-in user can change any routine by its id |
| "Remove" is a plain button; no `destroy()` | Remove does nothing |
| `index` has no `orderBy` | rows are not sorted by time |

---

## ② Weekly sheet

The sheet is the grid **routines × 7 days** for one week. Only **this week** can be created or edited; older weeks are history.

### Pages

| Page | URL | View | Controller method |
|---|---|---|---|
| **Weekly sheet** (tick boxes) | `/daily-routine` → `/routine-weeks/{monday}` | `routine-weeks/show.blade.php` | `RoutineWeekController@current` → `@show` |
| **Edit sheet** (which routines are on this week) | `/routine-weeks/{monday}/edit` | `routine-weeks/edit.blade.php` | `RoutineWeekController@edit` |

Shared view pieces: `routine-weeks/layouts/routine-weeks-layout.blade.php` (header with "Edit sheet" and "Manage routines"), `components/routines/check-cell.blade.php` (one box — tick form, untick form, or display only).

### Routes (`routes/web.php`, `auth` group)

| Action | Method + URL | Route name | Controller |
|---|---|---|---|
| Menu link | `GET /daily-routine` | `daily-routine` | `current` (redirect to this Monday) |
| Show a week | `GET /routine-weeks/{date}` | `routine-weeks.show` | `show` |
| Create this week's sheet | `POST /routine-weeks` | `routine-weeks.store` | `store` (no date sent — server uses today's Monday) |
| Edit sheet form | `GET /routine-weeks/{date}/edit` | `routine-weeks.edit` | `edit` |
| Save edit sheet | `PATCH /routine-weeks/{date}` | `routine-weeks.update` | `update` |

`{date}` must match `\d{4}-\d{2}-\d{2}`.

### Controller: `RoutineWeekController`

| Method | What it does |
|---|---|
| `current` | Redirects to this week's Monday |
| `show` | Any date → redirect to its Monday; bad date → 404. Loads the sheet's routines + sub-tasks + this week's ticks (eager loading, no N+1), builds rows and cells, and the "done per day" footer |
| `store` | Creates this week's sheet, copying the **previous sheet's active** routines (first sheet ever: all active top-level routines). Sheet + pivot rows in one `DB::transaction`. Already exists → just show it |
| `edit` | This week only (else 403), sheet must exist (else 404). Lists active top-level routines + any already on the sheet |
| `update` | Same checks; validates `routine_ids` inline (`$request->validate`, top-level routines only) and `sync()`s the pivot |

### Form Request

**None.** `update` validates inline with `$request->validate([...])`.

### Models

- `RoutineWeek` — `#[Fillable]`: `week_start`. `routines()` belongsToMany (pivot `routine_week_items`). Static helpers: `forDate($date)`, `previousBefore($monday)`, `isEditableDay($day)`. **No `user()` relationship.**
- `Routine::weeks()` — the other side of the pivot.

### Tables

- `routine_weeks`: `id`, `user_id` (required), `week_start` (a Monday), timestamps. Unique `(user_id, week_start)`.
- `routine_week_items` (pivot): `routine_week_id`, `routine_id`.

### Box states on the sheet

| State | Looks like | Meaning |
|---|---|---|
| `done` | green ✓ | ticked (or, for a parent, all sub-tasks ticked) |
| `partial` | amber `1/3` | parent with some sub-tasks ticked |
| `open` | empty box | not done (clickable if the day is editable) |
| `parent` | empty box, not clickable | parent with no sub-task ticked |
| `locked` | dashed box | future day or old week |

### Test data

| File | What it does |
|---|---|
| `RoutineWeekFactory` | A sheet 1–200 weeks ago. State: `current()` (this Monday). **No `user_id`.** |

### Tests

- `RoutineWeekTest` — guest redirected · menu opens this week · any date → its Monday · bad date 404 · empty states · sheet shows routines, sub-tasks and status per day · only this week's past days + today editable · old week read-only · week across two months · relationships.
- `RoutineWeekSheetTest` — create button shown / hidden · create copies previous active routines · first sheet gets all active top-level · creating twice keeps one · edit page · old week / missing sheet cannot be edited · save adds/removes and keeps ticks · empty sheet · sub-tasks and unknown ids rejected · previous sheet = newest before the week.

---

## ③ Checks (ticks)

A tick is one row in `routine_checks`: **tick = create the row, untick = delete it.** No page of its own — the forms live in the weekly sheet's boxes.

### Routes (`routes/web.php`, `auth` group)

| Action | Method + URL | Route name | Controller |
|---|---|---|---|
| Tick | `POST /routine-checks` (`routine_id`, `done_on`) | `routine-checks.store` | `store` |
| Untick | `DELETE /routine-checks/{routine_check}` | `routine-checks.destroy` | `destroy` |

`Route::resource('routine-checks', ...)->only(['store', 'destroy'])`. Both redirect `back()`.

### Controller: `RoutineCheckController`

| Method | What it does |
|---|---|
| `store` | `RoutineCheck::create($request->validated())` |
| `destroy` | 403 if the day is not editable (`RoutineWeek::isEditableDay`), else deletes the row |

### Form Request: `StoreRoutineCheckRequest`

- `rules()`: `routine_id` exists · `done_on` is `Y-m-d`, **not in the future**, **not before this Monday**.
- `after()` (needs the database): the routine has **no sub-tasks** (parents are done automatically) · the routine (or its parent) is **on that week's sheet** · **not already ticked** that day.

### Model: `RoutineCheck`

`#[Fillable]`: `routine_id`, `done_on`. `routine()` belongsTo. Cast: `done_on` → date.

### Table: `routine_checks`

`id`, `routine_id` (FK, cascade), `done_on` (date), timestamps. Unique `(routine_id, done_on)` — the final safety net for "once per day".

### Test data

| File | What it does |
|---|---|
| `RoutineCheckFactory` | A tick for today on a new `Routine::factory()` (which gets its own new user). |

### Tests: `RoutineCheckTest`

guest cannot tick · tick today · tick an earlier day this week · sub-task tick when parent is on the sheet · future day refused · old week refused · parent cannot be ticked directly · routine not on the sheet refused · no sheet → nothing tickable · same day twice refused · unknown routine refused · untick this week · old week cannot be unticked.

---

## Business rules (all sub-modules)

| Rule | Sub-module | Where it is enforced |
|---|---|---|
| Routines are **deactivated, not deleted**, so old sheets keep their history | ① | `RoutineController@deactivate` |
| A week starts on **Monday**; any date in the URL is redirected to its Monday | ② | `RoutineWeekController@show` |
| Only **this week** can get a new sheet; old weeks are history | ② | `RoutineWeekController@store` (uses today's Monday, no date sent) |
| A new sheet copies the **previous sheet's active** routines (or all active top-level routines if there is none) | ② | `RoutineWeekController@store` |
| Only this week's sheet can be edited (403 otherwise) | ② | `RoutineWeekController@edit` / `@update` |
| Only top-level routines go on a sheet | ② | `RoutineWeekController@update` validation |
| Tick only **this week, up to today** (no future days, old weeks read-only) | ③ | `StoreRoutineCheckRequest` rules, `RoutineWeek::isEditableDay()` on untick |
| A routine **with sub-tasks** is never ticked directly — it is done when **all** sub-tasks are done | ③ | `StoreRoutineCheckRequest::after()`, `Routine::statusOn()` |
| A routine must be **on that week's sheet** to be ticked | ③ | `StoreRoutineCheckRequest::after()` |
| Once per routine per day | ③ | `StoreRoutineCheckRequest::after()` + unique index |

---

## Local test data

```bash
docker compose exec app php artisan db:seed --class=RoutineOnlySeeder   # ① adds 10 routines for the first user (not emptied first)
docker compose exec app php artisan db:seed --class=RoutineSeeder       # ①②③ empties the 4 routine tables, adds routines + 2 sheets + ticks
```

⚠️ `RoutineSeeder` does not give the rows an owner: every routine gets a **new** user from the factory, and its sheets fail because `routine_weeks.user_id` is required. Never run seeders in production.

---

## User ownership: what must change

`user_id` was added to `routines` and `routine_weeks` **after** the module was built (25 Nov 2026). The tables are ready; most of the code does not use it yet.

### ① Routines

| # | File | Now | Must become | Status |
|---|---|---|---|---|
| 1 | Migration `add_user_id_to_routines_table` | `user_id` required + FK + cascade | — | ✅ |
| 2 | `Routine::user()`, `User::routines()` | exist | — | ✅ |
| 3 | `RoutineController@index` | `$request->user()->routines()` | — | ✅ |
| 4 | `RoutineController@store` | `Routine::create($request->all())` → **fails** | `$request->user()->routines()->create($request->validated())` | ⏳ |
| 5 | Form Requests | none | `StoreRoutineRequest`, `UpdateRoutineRequest`: `name` required, `target_time` `H:i`, `parent_id` must be **my** active **top-level** routine | ⏳ |
| 6 | `edit` / `update` / `activate` / `deactivate` | any user's routine | someone else's routine → 403 (`RoutinePolicy` + `authorize()`) | ⏳ |
| 7 | `destroy()` + "Remove" button | missing / does nothing | delete a sub-task (owner only); view becomes a `DELETE` form | ⏳ |
| 8 | `RoutineFactory` | `'user_id' => User::factory()`; `subTaskOf()` copies owner | — | ✅ |
| 9 | `RoutineOnlySeeder` | `->for($me)` | — | ✅ |
| 10 | `RoutineTest` | only "my" happy paths | add: another user's routine is hidden from the list; edit / update / activate / deactivate of it → 403; validation errors | ⏳ |

### ② Weekly sheet

| # | File | Now | Must become | Status |
|---|---|---|---|---|
| 1 | Migration `add_user_id_to_routine_weeks_table` | `user_id` required, unique `(user_id, week_start)` | — | ✅ |
| 2 | `RoutineWeek::user()`, `User::routineWeeks()` | missing | add both relationships | ⏳ |
| 3 | `RoutineWeek::forDate()` / `previousBefore()` | look at **all** users' sheets | take the user (or be called through `$user->routineWeeks()`) | ⏳ |
| 4 | `RoutineWeekController@store` | `RoutineWeek::create(...)` → **fails** (no `user_id`); first sheet copies **all** users' routines | create through `$request->user()->routineWeeks()`; copy only **my** routines | ⏳ |
| 5 | `RoutineWeekController@show` / `edit` | find any user's sheet; `edit` lists all users' routines | only my sheet, only my routines | ⏳ |
| 6 | `RoutineWeekController@update` | `exists:routines,id` (any user) | `Rule::exists(...)->where('user_id', ...)` — only my routines | ⏳ |
| 7 | `RoutineWeekFactory` | no `user_id` → **create fails** | `'user_id' => User::factory()` | ⏳ |
| 8 | `RoutineWeekTest`, `RoutineWeekSheetTest` | sheets with no owner | create with `->for($this->user)`; add: another user's sheet is not shown / not editable | ⏳ |

### ③ Checks

| # | File | Now | Must become | Status |
|---|---|---|---|---|
| 1 | Table `routine_checks` | owned through `routine_id` (no `user_id` needed) | — | ✅ |
| 2 | `StoreRoutineCheckRequest` | `exists:routines,id` (any user); sheet lookup via `forDate()` (any user) | routine must be **mine**; sheet lookup only in my sheets | ⏳ |
| 3 | `RoutineCheckController@destroy` | can delete any user's tick | 403 unless `$routineCheck->routine->user_id` is me | ⏳ |
| 4 | `RoutineCheckFactory` | owner comes from `Routine::factory()` | — | ✅ |
| 5 | `RoutineCheckTest` | only "my" ticks | add: cannot tick / untick another user's routine | ⏳ |

### Shared

| # | File | Now | Must become | Status |
|---|---|---|---|---|
| 1 | `RoutineSeeder` | rows have no single owner; sheets fail | `$me = User::first()`; `->for($me)` on routines and sheets | ⏳ |
| 2 | API (`routes/api.php`) | only `GET /api/user` (Sanctum) | routine / sheet / check endpoints, later — same ownership rules | ⏳ later |

Suggested order: ① (Form Requests → store → Policy → destroy → tests) → ② (relationships → factory → helpers → controller → tests) → ③ → seeder → API.

---

*Last updated: 26 November 2026*
