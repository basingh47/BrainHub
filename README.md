# 🧠 BrainHub

> A modern personal productivity and knowledge management platform built with Laravel.

## About

BrainHub is a personal productivity application designed to help manage daily tasks, goals, notes, calendars, and other productivity tools in one place.

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

- Authentication ✅ (Laravel Breeze)
- Dashboard
- Task Management
- Goals Management
- Notes
- Calendar
- User Profile ✅ (edit profile, change password, delete account)
- Settings

---

## Documentation

All docs are in [`docs/`](docs/README.md):

- [Architecture](docs/architecture.md) — stack, code-to-live flow, database
- [Pull request workflow](docs/git-workflow/pull-requests.md) — branches, commits, PRs
- [GitHub Actions CI](docs/ci-cd/github-actions.md) — checks and how to fix failures
- [Deployment](docs/deployment/render-and-local.md) — Render settings and local Docker Compose

---

## Run locally

```bash
docker compose up -d --build
```

Open http://localhost:8080. Migrations run automatically.

---

## Project Status

🚧 Version 1 in development

Done: project setup, CI, live deployment on Render, authentication and user profile. Next: dashboard and productivity features.

---

## Author

**Ankit Singh Bhadauriya**

Junior Laravel Developer
