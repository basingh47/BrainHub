# Docker v1 — Production on Render

## What production uses

```
User → Render (builds Dockerfile) → Laravel app container
                ↓
         Aiven MySQL (DB_* env vars)
```

| Piece | Role |
|---|---|
| [`Dockerfile`](../../../Dockerfile) | Builds and starts the **app only** |
| [`.dockerignore`](../../../.dockerignore) | Keeps `.env`, `vendor`, etc. out of the build |
| Render Environment | `APP_KEY`, `APP_URL`, `DB_*`, … |
| Aiven | Managed MySQL |
| `docker-compose.yml` | **Not used** |

**App scope for this version:** welcome page.

---

## What the v1 Dockerfile does

```
FROM php:8.4-cli
  → official PHP image

apt-get + pdo_mysql + zip
  → talk to MySQL; Composer needs zip

COPY composer from composer:2
  → install PHP packages inside the build

WORKDIR /var/www/html
COPY . .
  → application code into the image

composer install --no-dev --optimize-autoloader
  → production PHP dependencies only

mkdir storage… + chmod
  → Laravel needs writable folders

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
  → Render injects $PORT; app must listen on it
```

One stage. No Node. No MySQL server inside the image.

---

## Required Render environment variables

| Variable | Typical value | Why |
|---|---|---|
| `APP_KEY` | `base64:…` | Encryption / sessions |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | Do not leak errors/SQL |
| `APP_URL` | your Render URL | Correct links |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | Aiven host | Not `127.0.0.1` |
| `DB_PORT` | Aiven port | |
| `DB_DATABASE` | database name | |
| `DB_USERNAME` / `DB_PASSWORD` | Aiven user | |

`.env` is **not** copied into the image. Render supplies these at runtime.

---

## Deploy flow

1. Push to the branch Render watches (often `main`).
2. Render builds the repo `Dockerfile`.
3. Render starts the container and injects env vars.
4. App connects to Aiven using `DB_*`.

Changing `docker-compose.yml` does **nothing** to this flow.

---

## Production rules (v1)

1. **App image ≠ database.** MySQL stays on Aiven.  
2. **No secrets in Git or in the image.**  
3. **`APP_DEBUG=false` in production.**  
4. **Aiven must allow Render** to connect.  
5. **`php artisan serve` is fine for this MVP** welcome-page deploy.

---

*See also: [why this version](./01-why-this-version.md) · [local compose](./03-local-compose.md)*
