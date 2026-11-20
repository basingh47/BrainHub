# 🗺️ BrainHub Roadmap

The checklist for Version 1. Version 1 is **three pages** — details, rules and step-by-step progress live in the page plans under [plan/](plan/README.md); this file is the one-page overview.

---

# Version 1.0 — Foundation (MVP)

**Status:** 🚧 In Development

## Project Setup

- [x] Laravel project, GitHub repository
- [x] Git workflow: `feature/*` → `develop` → release to `main` (both protected)
- [x] CI: Pint, Larastan, PHPUnit, `composer audit`, SonarCloud
- [x] Docker (NGINX + PHP-FPM), local Docker Compose with `.env` as single source
- [x] Render deployment + Aiven MySQL (SSL)
- [x] Production environment configured
- [x] Tests never touch the local MySQL (`phpunit.xml` forces SQLite)

## Authentication (Laravel Breeze)

- [x] Register, login, logout
- [x] Forgot / reset password (needs a mail service in production)
- [ ] Email verification (code in place, not enforced yet)
- [x] Edit profile, change password, delete account
- [x] `/` redirects to the sign-up page

## Page 1 — Daily Tasks · [plan](plan/01-daily-tasks.md)

- [x] Add, edit, delete tasks (title, description, category, priority, due date and time)
- [x] Complete / reopen, move to another day (popover)
- [x] Today's list (open first, done last), overdue box, stat cards, flash messages
- [x] 17 feature tests · ✅ **Released**
- [ ] v1.1 polish: enums, badge components, undo, delete confirmation

## Page 2 — Daily Routine · [plan](plan/02-daily-routine.md)

- [x] Tables: routines (with sub-tasks), weekly sheets, sheet items, ticks
- [x] Weekly sheet grid, today highlighted, future days locked, old weeks read-only
- [x] Tick / untick (this week, up to today), parent done when all sub-tasks are done
- [x] Manage routines: add, sub-tasks, edit, activate / deactivate
- [x] Create this week's sheet (copy last sheet), edit sheet (checkboxes)
- [x] 46 feature tests · ✅ **Released so far**
- [ ] Polish: validation for routine forms, sub-task remove, create-box preview
- [ ] Day view with progress ("2 / 3 done")
- [ ] Old weeks list

## Page 3 — Dashboard · [plan](plan/03-dashboard.md)

- [ ] Greeting and today's date
- [ ] Today's task counts and overdue count
- [ ] Short list of today's tasks
- [ ] Today's routine progress
- [ ] Quick links to the other two pages

## Release

- [ ] All three pages meet their acceptance criteria
- [ ] Pages work on mobile and desktop widths
- [ ] Live site checked end to end
- [ ] Version 1 release

---

## Later (after Version 1)

- Multi-user data (`user_id` on app tables)
- Faster live pages (database in the same region as the app, cookie sessions)
- Mail service for password reset
- Monitoring (e.g. Laravel Pulse or a hosted tool)

---

Last Updated: 20 November 2026
