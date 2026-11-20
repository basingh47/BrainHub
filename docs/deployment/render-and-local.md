# Deployment — Production and local

## Production (Render)

```
User → Cloudflare → Render proxy (HTTPS) → container :8080
                                             NGINX → PHP-FPM → Laravel
                                                                  ↓
                                                            Aiven MySQL (SSL)
```

### Required Render settings

| Setting | Where | Value |
|---|---|---|
| `PORT` | Environment | `8080` — NGINX listens on 8080, not 10000 |
| Health Check Path | Settings → Health Checks | `/up` |

Render's default `PORT` is 10000. Setting it to `8080` tells Render where NGINX is listening,
instead of relying on Render's port auto-detection.

### Render environment

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

If the deploy waits on "default database connection", the Aiven free database is powered off or rebuilding — wait until Aiven shows **Running**, then redeploy.

The Render free disk is temporary: anything written to the container's disk (SQLite file, uploads, file sessions) is lost on every deploy or restart. That is why the database is external (Aiven).

---

## Local (Docker Compose)

`docker-compose.yml` runs the same image with a local MySQL, on port **8080**, plus a `node` container that rebuilds Tailwind on every save.

### Settings: `.env` is the single source

| Service | Gets its settings from |
|---|---|
| `app` | `env_file: .env` — **every** `.env` line becomes a real environment variable in the container |
| `mysql` | `${DB_DATABASE}`, `${DB_USERNAME}`, `${DB_PASSWORD}`, `${DB_ROOT_PASSWORD}` from `.env` (applied only when the volume is first created) |

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose up -d --force-recreate app
```

Two rules that follow from this:

1. **The container reads `.env` only when it starts.** After editing `.env`, run `docker compose up -d --force-recreate app`.
2. **Real environment variables beat `.env`.** Laravel never overwrites a variable that is already set. That is why Render ignores `.env` (its dashboard variables are real environment variables), and why `phpunit.xml` uses `<server>` + `force="true"` to make tests use SQLite.

### Which database does a command use?

All commands run inside the `app` container (`docker compose exec app php artisan …`).

| Command | Database |
|---|---|
| `migrate`, `db:seed`, `tinker`, … | the local `mysql` container (from `.env`) |
| `test` | SQLite in memory (`phpunit.xml` forces it) — never MySQL |

### Checks

| URL | Expected |
|---|---|
| http://localhost:8080/ | Redirects to `/register` |
| http://localhost:8080/login | Login page (styled) |
| http://localhost:8080/up | Health check — 200 |
| http://localhost:8080/.env | 403 (blocked by NGINX) |

Migrations run automatically on start — no `exec` step needed.

Useful commands:

```bash
docker compose exec app php artisan about          # versions, drivers, environment
docker compose exec app php artisan route:list     # all URLs
docker compose restart node                        # new Tailwind classes not showing
```
