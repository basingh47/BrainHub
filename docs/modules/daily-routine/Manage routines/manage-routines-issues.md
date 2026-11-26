# Manage routines: bugs and improvements

Scope: only the **Manage routines** page. That covers `RoutineController`, `routines/index.blade.php`, `routines/edit.blade.php` and the `routines.*` web routes. The weekly sheet and checks are not covered here.

Suggested order: B1 + B2 (Form Requests) → B3 (Policy) → B4 + I1 (Remove) → I2–I5 → I6–I10.

---

## Bugs

| # | Where | Problem | Effect | Fix |
|---|---|---|---|---|
| B1 | `RoutineController@store` | `Routine::create($request->all())` doesn't set `user_id` and has no validation | **"Save routine" crashes** (`user_id` is NOT NULL). An empty name or bad time is not caught. `parent_id` can point to **another user's** routine or to a sub-task. | Add `StoreRoutineRequest`: `name` required, max 255; `target_time` nullable `H:i`; `parent_id` must be **my** active **top-level** routine. Save with `$request->user()->routines()->create($request->validated())` |
| B2 | `RoutineController@update` | `$routine->update($request->all())`, no validation | A changed form can set `is_active`, `parent_id` or `sort_order` directly. A routine can be made its own parent, or a sub-task of a sub-task. | Add `UpdateRoutineRequest` with the same rules plus "not itself". Use `validated()` |
| B3 | `edit` / `update` / `activate` / `deactivate` | No owner check | **Any logged-in user can view or change any routine** by changing the id in the URL (IDOR) | Add `RoutinePolicy` (`$user->id === $routine->user_id`) and `$this->authorize(...)` → 403 |
| B4 | `routes/web.php` | `Route::resource(...)->except(['show','create'])` registers `DELETE /routines/{routine}`, but there is no `destroy()` | Calling it gives an error | Add `destroy()` (see I1), or also add `'destroy'` to `except` |

## Improvements

| # | Where | Now | Better |
|---|---|---|---|
| I1 | `index.blade.php`: sub-task **Remove** button | `type="button"`, does nothing | Make it a form. Prefer **deactivate** over delete, so old sheets keep their history (same rule as top-level routines) |
| I2 | `RoutineController@index` | No `orderBy`, so rows come out in id order | `orderByDesc('is_active')->orderBy('target_time')`: active first, sorted by time like the sheet |
| I3 | `store` (sub-tasks) | `sort_order` is never set (always 0), so sub-task order is random | Set it to `max(sort_order) + 1` of the siblings when creating. Up/down buttons can come later |
| I4 | `store` / `update` | No flash message (only activate/deactivate have one) | `->with('flash', ['type' => 'success', 'message' => ...])` |
| I5 | `index.blade.php`: Time column | Shows raw `06:30:00` | Show `06:30` (like `edit` does, or a model accessor) |
| I6 | `index.blade.php`: Deactivate | No confirmation, so one misclick hides the routine | `onsubmit="return confirm('Deactivate …?')"` |
| I7 | `index.blade.php`: Add form | "Target time" is shown even for a sub-task | Hide it when "Part of" is chosen (Alpine.js `x-show`) |
| I8 | `index.blade.php`: sub-task rows | Inactive sub-tasks look like active ones | Grey them out with an "Inactive" badge (needed once I1 uses deactivate) |
| I9 | `index.blade.php` | Outdated comments: "store() is empty until step 4", "Step 4: becomes a PATCH form" | Remove or update them |
| I10 | `tests/Feature/Routine/RoutineTest.php` | Only "happy path" tests | Add tests for validation errors, another user's routine hidden from the list and returning 403 on edit/update/activate/deactivate, and the `parent_id` rules |

---

*Last updated: 26 November 2026*
