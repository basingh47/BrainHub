# Docker v3 — Production and local

## Production (Render)

```
User → Cloudflare → Render proxy (HTTPS) → container :8080
                                             NGINX → PHP-FPM → Laravel
                                                                  ↓
                                                            Aiven MySQL (SSL)
```

### ⚠️ Required Render changes before deploying v3

| Setting | Where | Value |
|---|---|---|
| `PORT` | Environment | `8080` — NGINX listens on 8080, not 10000 |
| Health Check Path | Settings → Health Checks | `/up` |

Render's default `PORT` is 10000. Setting it to `8080` tells Render where NGINX is listening,
instead of relying on Render's port auto-detection.

### Render environment (unchanged from v2)

| Variable | Note |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_KEY`, `APP_URL` | `APP_URL` = `https://brainhub-hwfr.onrender.com` |
| `DB_*` | Aiven host, port, database, user, password |
| `MYSQL_ATTR_SSL_CA` | Path to Aiven CA cert, if SSL verification is used |
| `MAIL_*` | Real mailer for password reset |

### What happens on each deploy

1. Render builds the `Dockerfile`
2. Container starts → `storage:link` → waits for DB → `migrate --force` → `optimize`
3. S6 starts PHP-FPM, then NGINX
4. Render calls `/up` → 200 → traffic switches to the new container

If migrations fail, the container does not start and Render keeps the old version running.

---

## Local (Docker Compose)

Same `docker-compose.yml` (app + local MySQL), port changed to **8080**:

```bash
docker compose build
docker compose up
```

| URL | Expected |
|---|---|
| http://localhost:8080/ | Welcome |
| http://localhost:8080/login | Login page (styled) |
| http://localhost:8080/up | Health check — 200 |
| http://localhost:8080/.env | 403 (blocked by NGINX) |

Migrations run automatically on start — no `exec` step needed.

Artisan inside the container:

```bash
docker compose exec app php artisan about
```

---

## v2 vs v3 checklist

| Topic | v2 | v3 |
|---|---|---|
| Web server | `artisan serve` | NGINX + PHP-FPM |
| Parallel requests | No | Yes (6 PHP workers) |
| Static files | Through PHP | NGINX, gzip, 1-year cache |
| OPcache | Off | On |
| Migrations on deploy | Manual | Automatic |
| Config/route/view cache | No | Yes (`optimize`) |
| Runs as | root | `www-data` |
| Port | 10000 | **8080** |
| MySQL in image | No | No (Aiven / Compose) |

---

*See also: [problem](./01-problem-why-v2-not-enough.md) · [Dockerfile solution](./02-solution-dockerfile.md) · [Docker-v2](../Docker-v2/)*
