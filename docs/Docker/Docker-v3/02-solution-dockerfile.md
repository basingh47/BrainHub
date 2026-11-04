# Solution: Docker-v3 Dockerfile

## What we changed

Same three stages as v2 — only the **base images** changed:

```
Stage 1  vendor   serversideup/php:8.4-cli        → Composer PHP packages
Stage 2  assets   node:22-alpine                  → npm ci && npm run build  (discarded)
Stage 3  final    serversideup/php:8.4-fpm-nginx  → app + vendor + public/build  (Render runs this)
```

No more `CMD php artisan serve` — the base image starts S6, which starts NGINX + PHP-FPM.

---

## Why serversideup/php

[serversideup/php](https://serversideup.net/open-source/docker-php/) is a production PHP image built for Laravel.
It already includes what we would otherwise write ourselves:

| We get | Instead of writing |
|---|---|
| NGINX config (Laravel `public/` root, blocks `.env` / dotfiles, gzip, asset caching) | `nginx/default.conf` |
| PHP-FPM tuned via env vars | `www.conf` |
| S6 supervisor (restarts NGINX / PHP-FPM if they crash) | `supervisord.conf` |
| Laravel startup automations | `entrypoint.sh` |
| Composer, `pdo_mysql`, `opcache`, `zip`, … preinstalled | `docker-php-ext-install …` |
| Runs as `www-data` on port 8080 (non-root) | user / permission setup |

---

## Settings in the Dockerfile (`ENV`)

These are **defaults**; Render or Compose environment variables override them.

| Variable | Value | Why |
|---|---|---|
| `PHP_OPCACHE_ENABLE` | `1` | Keeps compiled PHP in memory — big speed-up |
| `PHP_FPM_PM_MAX_CHILDREN` | `6` | Render free has 512 MB; ~40 MB per worker |
| `AUTORUN_ENABLED` | `true` | On every start: `storage:link`, `migrate --force`, `optimize` |
| `HEALTHCHECK_PATH` | `/up` | Docker health check boots Laravel, not just NGINX |
| `LOG_CHANNEL` | `stderr` | Laravel logs appear in Render's log viewer |

`AUTORUN_ENABLED` means **migrations run automatically on deploy**
(the image waits up to 30 s for the database first). No more Render shell step.

---

## Other changes

### `bootstrap/app.php` — trust the proxy

```php
$middleware->trustProxies(at: '*');
```

Render handles HTTPS and forwards plain HTTP to the container with `X-Forwarded-Proto: https`.
Without this, Laravel thinks the request is HTTP and builds `http://…/build/app.css`
→ browser blocks it as mixed content → unstyled pages.
`'*'` is safe because only Render's proxy can reach the container.

### `.dockerignore`

Now also excludes `docs`, `tests`, `public/build`, `bootstrap/cache/*.php`, and the stray
`laravel` SQLite file in the repo root, so they never end up in the image.

---

*Next: [03-production-and-local.md](./03-production-and-local.md)*
