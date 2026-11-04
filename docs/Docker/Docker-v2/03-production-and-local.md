# Docker v2 — Production and local

## Production (Render)

Same pattern as v1 — only the **image contents** grew (Vite assets):

```
User → Render (builds Dockerfile v2) → app with public/build
                ↓
         Aiven MySQL
```

| Piece | Role |
|---|---|
| `Dockerfile` (v2 multi-stage) | App + compiled CSS/JS |
| Render env | `APP_KEY`, `APP_URL`, `DB_*`, `MAIL_*`, … |
| Aiven | MySQL |
| `docker-compose.yml` | **Not used** |

### Extra Render notes for Breeze

| Variable | Note |
|---|---|
| `APP_DEBUG` | `false` |
| `APP_URL` | Must match public URL (signed / verify links) |
| `MAIL_*` | Real mailer for password reset (not `log` in production) |
| `DB_*` | Aiven — users/sessions tables via migrations |

Run migrations when you deploy auth (Render shell, one-off job, or a later CMD step):

```bash
php artisan migrate --force
```

---

## Local (Docker Compose)

`docker-compose.yml` is unchanged in role:

- Builds the **same** root `Dockerfile` (now v2)
- Starts `mysql` as before
- Inline env — no `.env` required for the smoke test

```powershell
docker compose build --no-cache
docker compose up
```

| URL | Expected (v2) |
|---|---|
| http://localhost:10000/ | Welcome |
| http://localhost:10000/login | Login page loads (styled) |

Artisan inside the container:

```powershell
docker compose exec app php artisan migrate --force
```

---

## v1 vs v2 checklist

| Topic | v1 | v2 |
|---|---|---|
| Welcome `/` | Yes | Yes |
| Breeze `@vite` pages | No | Yes |
| Node in final image | No | No (build stage only) |
| MySQL in Dockerfile | No | No |
| Compose on Render | No | No |

---

*See also: [problem](./01-problem-why-v1-not-enough.md) · [Dockerfile solution](./02-solution-dockerfile.md) · [Docker-v1](../Docker-v1/)*
