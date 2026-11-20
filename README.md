# 🧠 BrainHub

> A modern personal productivity and knowledge management platform built with Laravel.

## About

BrainHub is a personal productivity application: plan the day's tasks, track daily routines on a weekly sheet, and see progress at a glance.

The project is being developed as a long-term portfolio and learning project using modern Laravel development practices, clean architecture, and production-ready workflows.

---

## Current Version

**Version:** `1.0.0 (In Development)`

Version 1 focuses on a stable foundation with essential productivity features.

---

## Tech Stack

### Backend
- Laravel
- PHP
- MySQL (Aiven)
- Eloquent ORM

### Frontend
- Blade
- Tailwind CSS
- Alpine.js

### Deployment
- Docker (NGINX + PHP-FPM)
- Render
- GitHub Actions (CI)

---

## Features (Version 1)

Version 1 is three pages ([product plan](docs/plan/README.md)):

| Page | Status |
|---|---|
| **Daily Tasks** — add, edit, complete, reopen, move and delete one-off tasks; overdue box | ✅ Released |
| **Daily Routine** — routines with sub-tasks, a weekly sheet per Monday, tick / untick days, create and edit the sheet | 🚧 Released in parts (day view and old-weeks list next) |
| **Dashboard** — read-only summary of today | ⏳ Planned |

Also: authentication and user profile (Laravel Breeze) ✅

---

## Documentation

All docs are in [`docs/`](docs/README.md):

- [Architecture](docs/architecture.md) — stack, code-to-live flow, database
- [Database design](docs/database-design.md) — tables and relationships
- [Product plan](docs/plan/README.md) — the three pages, rules and progress
- [Pull request workflow](docs/git-workflow/pull-requests.md) — feature → `develop` → release to `main`
- [GitHub Actions CI](docs/ci-cd/github-actions.md) — checks and how to fix failures
- [Deployment](docs/deployment/render-and-local.md) — Render settings and local Docker Compose

---

## Run locally

```bash
cp .env.example .env            # local settings — docker-compose reads everything from .env
docker compose up -d --build
docker compose exec app php artisan key:generate      # writes APP_KEY into .env
docker compose up -d --force-recreate app             # the container reads .env only when it starts
docker compose exec app php artisan db:seed --class=RoutineSeeder     # optional sample data
docker compose exec app php artisan db:seed --class=DailyTaskSeeder   # optional sample data
```

Open http://localhost:8080 and register. Migrations run automatically.

Run the tests (always on SQLite in memory — they never touch your MySQL):

```bash
docker compose exec app php artisan test
```

---

## Project Status

🚧 Version 1 in development

Done: project setup, CI, live deployment on Render, authentication, Daily Tasks, Daily Routine (routines, weekly sheet, ticks, create / edit sheet). Next: Daily Routine day view and old-weeks list, then the Dashboard.

---

## Author

**Ankit Singh Bhadauriya**

Junior Laravel Developer
