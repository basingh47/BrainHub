# 🏗️ BrainHub Architecture

How BrainHub is built, checked, and deployed. For planned features see [roadmap.md](roadmap.md).

---

## Overview

BrainHub is a single Laravel application (server-rendered Blade pages) with Laravel Breeze authentication, a MySQL database on Aiven, and one Docker container on Render.

Version 1 has three pages — **Daily Tasks** (released), **Daily Routine** (weekly sheet, released in parts) and **Dashboard** (planned). See [plan/README.md](plan/README.md).

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13, PHP 8.4 |
| Pages | Blade + Tailwind CSS + Alpine.js (built by Vite) |
| Auth | Laravel Breeze (register, login, password reset, email verification, profile) |
| Database | MySQL on Aiven (production), MySQL container (local), SQLite in-memory (tests) |
| Web server | NGINX + PHP-FPM (`serversideup/php` image) |
| Hosting | Render (one container) |
| CI | GitHub Actions |
| Code checks | Pint (style), Larastan (static analysis), PHPUnit (tests), `composer audit` (security), SonarCloud (quality) |

---

## From code to live

```
 1. Developer branches from develop, pushes, opens a PR into develop
                     │
                     ▼
 2. GitHub Actions runs CI  (.github/workflows/ci.yml)
      Pint → Larastan → PHPUnit → composer audit → SonarCloud
                     │
                     ▼
 3. PR is merged into develop (branch rule: PR required + "ci" check must pass)
                     │
                     ▼
 4. Release PR develop → main  (CI runs again on the combined code) → merged
                     │
                     ▼
 5. Render sees the new commit on main and builds the Dockerfile
                     │
                     ▼
 6. New container starts: storage:link → migrate → optimize → NGINX + PHP-FPM
                     │
                     ▼
 7. Render calls /up → 200 OK → traffic switches to the new container → LIVE
```

If any CI check fails, the PR cannot be merged. If the new container fails (for example a migration error), Render keeps the old version running.

### CI checks (step 2)

| Step | Command | Fails the PR when |
|---|---|---|
| Build assets | `npm ci && npm run build` | Frontend build breaks |
| Code style | `vendor/bin/pint --test` | Code is not formatted |
| Static analysis | `vendor/bin/phpstan analyse` | Type or logic errors found |
| Tests | `vendor/bin/phpunit --coverage-clover=coverage.xml` | Any test fails |
| Security | `composer audit` | A package has a known vulnerability |
| Code quality | SonarCloud scan | Scan fails (e.g. invalid `SONAR_TOKEN`) |

CI runs on PRs into `main` and `develop`, on pushes to `main` (after a merge) and `develop`, and manually.

---

## Which file is used by what

| File | Used by | Purpose |
|---|---|---|
| `.github/workflows/ci.yml` | GitHub Actions | Defines the CI steps above |
| `sonar-project.properties` | SonarCloud step in GitHub Actions | Project key, organization, source/test folders, `coverage.xml` path |
| `phpstan.neon` | Larastan step in CI | Analysis level (4) and folders (`app/`) |
| `phpunit.xml` | PHPUnit (CI and local) | Test settings — forces SQLite in memory (`<server>` + `force="true"`), so tests never touch MySQL, even inside Docker |
| `.nvmrc` | CI (`setup-node`) | Node version |
| `Dockerfile` | **Render** (and local `docker compose`) | Builds the live image |
| `.dockerignore` | Docker build | Keeps `vendor`, `node_modules`, `.env`, `docs`, `tests` out of the image |
| `docker-compose.yml` | Local only (Render ignores it) | Runs `app` + `node` (Tailwind watch) + `mysql` on your machine |
| `.env` | Local only (never committed) | **Single source** of local settings: `app` gets every line (`env_file`), `mysql` reads `DB_*` from it. Copy from `.env.example` |
| Render dashboard | Render | Production environment variables, health check `/up`, Aiven CA secret file |

---

## Production

```
Browser ─HTTPS─▶ Render proxy ─▶ container :8080
                                  ├─ NGINX   (serves /build CSS/JS)
                                  └─ PHP-FPM (up to 6 workers) → Laravel
                                                    │ MySQL over SSL
                                                    ▼
                                              Aiven MySQL
```

- **One container** (Render free plan does not include background workers or cron jobs).
- **Image** built in three stages: Composer packages → Vite assets → final `serversideup/php:8.4-fpm-nginx` image. Node is not in the final image.
- **Settings** come from Render environment variables. See [deployment/render-and-local.md](deployment/render-and-local.md).

---

## Database

| Environment | Database |
|---|---|
| Production | Aiven MySQL (SSL, CA file at `/etc/secrets/aiven-ca.pem`) |
| Local | `mysql:8.0` container from `docker-compose.yml` |
| CI tests | SQLite in-memory (`phpunit.xml`) |

Tables (from `database/migrations/`) — columns and relationships in [database-design.md](database-design.md):

| Table | Used for |
|---|---|
| `users`, `password_reset_tokens` | Accounts and password reset |
| `daily_tasks` | Daily Tasks page |
| `routines`, `routine_weeks`, `routine_week_items`, `routine_checks` | Daily Routine page (habits + sub-tasks, weekly sheets, ticks) |
| `sessions` | Logged-in sessions (`SESSION_DRIVER=database`) |
| `cache`, `cache_locks` | Cache (`CACHE_STORE=database`) |
| `jobs`, `job_batches`, `failed_jobs` | Queue (not used in production yet, no worker) |

Migrations run automatically every time the container starts.

---

## Application structure

```
app/Http/Controllers/
    DailyTaskController       Daily Tasks: list, add, edit, complete, reopen, move, delete
    RoutineWeekController     Daily Routine: weekly sheet, create sheet, edit sheet
    RoutineCheckController    tick / untick a routine on a day
    RoutineController         manage routines and sub-tasks, activate / deactivate
    ProfileController         profile (Breeze)
    Auth/                     Breeze auth controllers
app/Http/Requests/            Form validation (tasks, ticks, login, profile)
app/Models/                   User, DailyTask, Routine, RoutineWeek, RoutineCheck
routes/web.php                / → /register, /dashboard, /profile, /daily-tasks, /daily-routine, /routine-weeks, /routine-checks, /routines
routes/auth.php               login, register, password reset, email verification
resources/views/              daily-tasks/, routine-weeks/, routines/, layouts, components (flash, daily-tasks/, routines/)
database/factories, seeders   Fake data for tests and local development (never run seeders in production)
tests/Feature/                Auth, profile, DailyTaskTest, Routine/ (weekly sheet, ticks, manage routines, create / edit sheet)
```

---

## Known gaps

- No queue worker or scheduler in production (Render free plan).
- Mail is not configured, so password reset emails are not sent.
- Uploaded files would be lost on redeploy (no S3/R2 yet).
- Pages that use the database take several seconds on the live site: Render and Aiven are far apart, and each request opens a new SSL connection (measured: `/up` 0.35 s vs `/login` 3.5 s). Options: same region for app and DB, cookie sessions.
- Single user: app tables have no `user_id` yet (every account sees the same data).
- SonarCloud token (`SONAR_TOKEN`) expires and must be renewed (see [sonarcloud.md](ci-cd/sonarcloud.md)).

---

*Last updated: 20 November 2026*
