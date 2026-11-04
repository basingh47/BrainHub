# Docker v3 — NGINX + PHP-FPM (production web server)

**Status:** Current (replaces v2)  
**Date:** November 2026  
**App scope:** Same as v2 (Breeze auth) — only the **web server** changed  

---

## Documents in this folder

| Doc | What it covers |
|---|---|
| [01-problem-why-v2-not-enough.md](./01-problem-why-v2-not-enough.md) | Why `php artisan serve` is not a production server |
| [02-solution-dockerfile.md](./02-solution-dockerfile.md) | The `serversideup/php` image and what each setting does |
| [03-production-and-local.md](./03-production-and-local.md) | Render settings (port 8080!) + local Compose |

---

## Short answer

| Version | Web server | Good for |
|---|---|---|
| [Docker-v1](../Docker-v1/) | `artisan serve` | Welcome page only |
| [Docker-v2](../Docker-v2/) | `artisan serve` | Breeze pages, but one request at a time |
| **Docker-v3 (this)** | **NGINX + PHP-FPM** | Real traffic — parallel requests, fast static files |

**Problem:** `artisan serve` is PHP's built-in dev server — single-threaded, PHP serves every CSS/JS file.  
**v3** uses `serversideup/php:8.4-fpm-nginx`: NGINX and PHP-FPM in one container, kept alive by S6.

---

## Quick picture

```
v2:  Render → artisan serve :10000 → Laravel          (one request at a time)

v3:  Render → NGINX :8080 ─┬─ /build/*  → served directly (gzip, 1-year cache)
                           └─ *.php     → PHP-FPM (up to 6 workers) → Laravel
                 S6 supervises both, restarts either if it crashes
```

Still **one container** — Render free runs one container per service.
