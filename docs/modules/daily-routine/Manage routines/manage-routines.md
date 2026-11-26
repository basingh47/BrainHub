# ① Manage routines: how it works

The list of habits and their sub-tasks. Routines are **deactivated, not deleted**, so old sheets keep their history.

Other sub-modules (weekly sheet, checks) are covered in [daily-routine.md](../daily-routine.md).
Bugs and improvements for this page: [manage-routines-issues.md](manage-routines-issues.md).

## Screenshots

**Manage routines** (`/routines`): the table on the left, the "Add routine" form on the right.

![Manage routines page](images/manage-routines.png)

**Edit routine** (`/routines/{routine}/edit`), shown here for a sub-task:

![Edit routine page](images/edit-routine.png)

*Taken 26 Nov 2026 with local test data. The repeated rows come from running `RoutineOnlySeeder` more than once. The bar at the bottom is Laravel Debugbar (local only).*

## Pages

| Page | URL | View | Controller method |
|---|---|---|---|
| **Manage routines** (table + "Add routine" form) | `/routines` | `routines/index.blade.php` | `RoutineController@index` |
| **Edit routine** | `/routines/{routine}/edit` | `routines/edit.blade.php` | `RoutineController@edit` |

Layout: `routines/layouts/routine-layout.blade.php` (header with "← Back to this week").

## Routes (`routes/web.php`, `auth` group)

| Action | Method + URL | Route name | Controller |
|---|---|---|---|
| List | `GET /routines` | `routines.index` | `index` |
| Add | `POST /routines` | `routines.store` | `store` |
| Edit form | `GET /routines/{routine}/edit` | `routines.edit` | `edit` |
| Save edit | `PATCH /routines/{routine}` | `routines.update` | `update` |
| Remove | `DELETE /routines/{routine}` | `routines.destroy` | ⚠️ **no `destroy()` method yet** |
| Deactivate | `PATCH /routines/{routine}/deactivate` | `routines.deactivate` | `deactivate` |
| Activate | `PATCH /routines/{routine}/activate` | `routines.activate` | `activate` |

`Route::resource('routines', ...)->except(['show', 'create'])` makes 5 routes. There's no `create` page because the form is on the index page. Activate and deactivate are written by hand above it.

## Controller: `RoutineController`

| Method | What it does |
|---|---|
| `index` | My top-level routines with their `children` (sub-tasks), plus `$parents` (the active ones, for the "Part of" dropdown) |
| `store` | `Routine::create($request->all())` |
| `edit` | Shows the edit form (route model binding: an unknown id gives 404) |
| `update` | `$routine->update($request->all())` |
| `deactivate` / `activate` | Sets `is_active` to false / true |

## Form Request

**None yet.** `store` and `update` take a plain `Request` and save `$request->all()` with no validation.

## Model: `Routine`

- `#[Fillable]`: `parent_id`, `name`, `target_time`, `sort_order`, `is_active`. `user_id` is left out on purpose.
- Relationships: `parent()`, `children()` (ordered by `sort_order`), `checks()`, `weeks()`, `user()`.
- `statusOn($day)`, `isDoneOn($day)` and `doneChildrenOn($day)` are used by the weekly sheet, not by this page.

## Table: `routines`

`id`, `user_id` (required), `parent_id` (nullable, FK → `routines`), `name`, `target_time` (nullable), `sort_order` (default 0), `is_active` (default true), timestamps. Index on `(is_active, parent_id)`.

## Test data

| File | What it does |
|---|---|
| `RoutineFactory` | An active top-level routine with a random name and time, owned by `User::factory()`. States: `subTaskOf($parent)` (same owner as the parent), `inactive()`. |
| `RoutineOnlySeeder` | **Adds** 10 routines for the **first user** without emptying the table first: Exercise + 3 sub-tasks, 5 random ones, and 1 inactive Meditate. Running it twice gives duplicates. |

## Tests: `tests/Feature/Routine/RoutineTest.php`

Guest is redirected · list shows routines and sub-tasks · add a routine · add a sub-task · edit page · update · unknown routine gives 404 · deactivate · activate.

---

*Last updated: 26 November 2026*
