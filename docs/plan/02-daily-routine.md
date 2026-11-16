# 2️⃣ Daily Routine

**Status:** ⏳ Not started · **Branch:** `feature/daily-routine` · **Depends on:** Daily Tasks done

## Goal

Track repeating morning and evening activities (exercise, reading, planning) and see today's progress, while keeping each day's history.

## User stories

- As a user, I can create, edit and delete routine items and group them into Morning or Evening.
- As a user, I can set an optional target time for a routine item.
- As a user, I can tick a routine item as done for today, and untick it.
- As a user, I can see today's progress, e.g. "Morning 3 / 4".
- As a user, a new day starts with nothing ticked, without losing previous days.

---

## UI

Page: `/routines`

| Section | Content |
|---|---|
| Header | "Daily Routine", today's date |
| Progress | Morning X / Y · Evening X / Y |
| Morning list | Items ordered by target time, each with a done checkbox |
| Evening list | Same as morning |
| Manage items | Add item (name, period, target time), edit, delete |

---

## Data model

**Key idea:** keep the routine **definition** separate from its **daily completion history**.

### `routines` — the reusable item

| Column | Type | Rules |
|---|---|---|
| `id` | bigint | Primary key |
| `name` | string | Required, e.g. "Exercise" |
| `period` | string | `morning` · `evening` |
| `target_time` | time, nullable | Optional |
| `sort_order` | integer | Default 0 |
| `is_active` | boolean | Default true — deactivate instead of losing history |
| `created_at`, `updated_at` | timestamps | Laravel |

### `routine_completions` — one row per item per day done

| Column | Type | Rules |
|---|---|---|
| `id` | bigint | Primary key |
| `routine_id` | foreignId | → `routines.id`, cascade on delete |
| `completed_on` | date | The day it was done |
| `created_at`, `updated_at` | timestamps | Laravel |

Unique index: (`routine_id`, `completed_on`) — an item can be done only once per day.

**No daily reset job:** a new day simply has no completion rows yet.

---

## Routes

| Action in UI | HTTP | URL | Method |
|---|---|---|---|
| Page | GET | `/routines` | `RoutineController@index` |
| Add item | POST | `/routines` | `RoutineController@store` |
| Edit item | PUT | `/routines/{routine}` | `RoutineController@update` |
| Delete item | DELETE | `/routines/{routine}` | `RoutineController@destroy` |
| Tick done today | POST | `/routine-completions` | `RoutineCompletionController@store` |
| Untick | DELETE | `/routine-completions/{completion}` | `RoutineCompletionController@destroy` |

Both are resource controllers limited with `->only([...])`.

---

## Business rules

1. Ticking creates a completion for **today**; unticking deletes it.
2. The same item cannot be completed twice on the same day (unique index + validation).
3. Progress = completed items today ÷ active items in that period.
4. Deactivating an item hides it from today's list but keeps its history.

---

## Implementation checklist

| Step | Work | Status |
|---|---|---|
| A | Migrations, `Routine` + `RoutineCompletion` models, `RoutinePeriod` enum, factories | ⏳ |
| B | `hasMany` / `belongsTo` relationships | ⏳ |
| C | Routine page: morning and evening lists | ⏳ |
| D | Add, edit, delete routine items (Form Requests) | ⏳ |
| E | Tick / untick for today | ⏳ |
| F | Progress counts | ⏳ |
| G | Feature tests | ⏳ |

---

## Acceptance criteria

- [ ] Routine items can be created, edited, deleted and grouped by period.
- [ ] An item can be ticked done for today and unticked.
- [ ] Today's morning and evening progress is shown correctly.
- [ ] The next day starts with nothing ticked; previous days' completions still exist.
- [ ] An item cannot be completed twice on the same day.
- [ ] Feature tests pass in CI.

## Out of scope (v1)

Custom periods beyond morning/evening, streaks and history charts, reminders.
