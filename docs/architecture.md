# 🏗️ BrainHub Architecture

This document describes the current technical architecture of BrainHub. For feature status see [roadmap.md](roadmap.md) and [progress.md](progress.md).

---

## Overview

BrainHub is a monolithic Laravel application: server-rendered Blade views, a MySQL database, and no separate frontend build/API layer (yet — a REST API is planned for v3, see roadmap). At this stage the codebase is still the default Laravel scaffold plus deployment tooling; application features have not been built yet.

---

## Stack

| Layer | Technology |
|---|---|
| Language / Framework | PHP 8.3+ (CI runs 8.4), Laravel ^13.8 |
| Templating | Blade |
| Frontend | Tailwind CSS, Alpine.js (planned, not yet wired up) |
| Database | MySQL, hosted on Aiven |
| Background jobs | Laravel queue (`jobs` table present, no workers configured yet) |
| Containerization | Docker (`php:8.4-cli` base image) |
| Hosting | Render |
| CI/CD | GitHub Actions |
| Static analysis | Larastan (PHPStan for Laravel) |
| Code style | Laravel Pint |
| Code quality | SonarCloud |
| Testing | PHPUnit |

---

## Application Structure

Standard Laravel directory layout — no custom architectural layers (services, repositories, actions, etc.) have been introduced yet.

```
app/
  Http/Controllers/   → only the base Controller.php exists
  Models/             → only the default User model exists
  Providers/          → default AppServiceProvider

database/
  migrations/         → only default users, cache, jobs tables
  factories/, seeders/ → default UserFactory/DatabaseSeeder

routes/
  web.php             → single "/" route returning the welcome view
  console.php         → default Artisan closures

resources/
  views/              → default welcome.blade.php
  css/, js/           → default Vite entrypoints
```

As features from the roadmap (Auth, Dashboard, Tasks, Goals, Notes, Calendar, Profile) are built, this section should be updated to reflect real controllers, models, and routes.

---

## Environments

- **Local development** — `php artisan serve` / Sail-less local PHP, SQLite or local MySQL via `.env`.
- **CI** — GitHub Actions spins up a fresh environment per run (PHP 8.4, SQLite for tests), runs Pint → Larastan → PHPUnit → `composer audit` → SonarCloud scan.
- **Production** — Docker image built from the repo `Dockerfile`, deployed to Render, connected to a managed Aiven MySQL instance. The container listens on `${PORT:-10000}` and serves via `php artisan serve`.

---

## CI/CD Pipeline

Defined in [.github/workflows/ci.yml](../.github/workflows/ci.yml):

1. Checkout (full history, for SonarCloud).
2. Set up PHP 8.4 with required extensions.
3. Install Composer dependencies (cached by `composer.lock` hash).
4. Bootstrap `.env` and `APP_KEY`.
5. `vendor/bin/pint --test` — fails the build on style violations.
6. `vendor/bin/phpstan analyse` — static analysis via Larastan.
7. `vendor/bin/phpunit --coverage-clover=coverage.xml` — test suite + coverage report.
8. `composer audit` — checks dependencies for known vulnerabilities.
9. SonarCloud scan (non-blocking until `SONAR_TOKEN` is configured).

Triggers: pushes to `develop`, PRs targeting `main`, and manual dispatch.

---

## Deployment

`Dockerfile` builds a production image:
- Base: `php:8.4-cli` with `pdo_mysql` and `zip` extensions.
- `composer install --no-dev --optimize-autoloader`.
- Storage/cache directories created and permissioned (`storage`, `bootstrap/cache`).
- Runs `php artisan serve --host=0.0.0.0 --port=${PORT:-10000}` — suitable for Render's port-binding model, but not a production-grade app server (no PHP-FPM/Nginx, no queue workers, no scheduler). Revisit before scaling past a single small instance.

---

## Known Gaps / Future Work

- No service/repository layer conventions decided yet — to be established once the first real feature (Authentication) is built.
- No queue worker or scheduler configured in production despite the `jobs` table existing.
- `php artisan serve` in Docker is fine for an MVP but should move to PHP-FPM + Nginx (or Octane) before real traffic.
- SonarCloud step is `continue-on-error` until `SONAR_TOKEN` is added as a repo secret.

---

*Last updated: 28 July 2026*
