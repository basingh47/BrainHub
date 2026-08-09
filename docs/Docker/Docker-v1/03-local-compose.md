# Docker v1 — Local `docker-compose.yml`

## Why we created this file

We already had a **`Dockerfile`** for Render (app + Aiven).

For **local learning on Windows Docker Desktop** we also wanted:

1. An **app** container (same Dockerfile as production)
2. A **MySQL** container (stand-in for Aiven)
3. Env vars without a project `.env` for this smoke test
4. One command to start both and see how they communicate

That is [`docker-compose.yml`](../../../docker-compose.yml).

**Local only.** Render never reads this file. Merging it to `main` does not change production.

---

## What Compose starts

```
docker compose up
        │
        ├── app    (build: Dockerfile)  → port 10000 → http://localhost:10000
        └── mysql  (image: mysql:8.0)   → port 3307 on host → 3306 in container
```

| Service | Image / build | Role |
|---|---|---|
| `app` | Build from root `Dockerfile` | Laravel (`artisan serve`) |
| `mysql` | `mysql:8.0` | Database for local smoke tests |

Env is inline in the YAML — no `.env` required for Compose in v1.

---

## How app talks to MySQL (like Render → Aiven)

```
app container                    mysql container
─────────────                    ───────────────
DB_HOST=mysql  ──── Docker ────► hostname "mysql"
DB_PORT=3306        network      port 3306
DB_DATABASE=brainhub
DB_USERNAME=brainhub
DB_PASSWORD=secret
```

| Local Compose | Render + Aiven |
|---|---|
| `DB_HOST=mysql` | `DB_HOST=….aivencloud.com` |
| Docker network | Internet |
| Same Laravel `DB_*` idea | Same Laravel `DB_*` idea |

Inside the app container, use service name **`mysql`**, not `127.0.0.1`.

---

## Commands (project root, Docker Desktop running)

```powershell
cd c:\laragon\www\Ankit-WorkSpace\PHP\Projects\BrainHub

docker compose build
docker compose up
```

| Command | Meaning |
|---|---|
| `docker compose build` | Build the app image from `Dockerfile` |
| `docker compose up` | Start app + MySQL |
| `docker compose down` | Stop containers |
| `docker compose ps` | Status |
| `docker compose logs -f app` | App logs |

Artisan inside the app container:

```powershell
docker compose exec app php artisan --version
docker compose exec app php artisan migrate --force
```

Browser: **http://localhost:10000** (welcome page)

Host port **3307** avoids clashing with Laragon MySQL on 3306.

---

## What you need on Windows

| Required | Not required for this smoke test |
|---|---|
| Docker Desktop running | Laragon PHP / Composer / Node |
| Repo with Dockerfile + compose | Project `.env` for Compose |

---

## Expected behaviour in v1

| Check | Expected |
|---|---|
| `docker ps` — app + mysql | Both **Up**; MySQL may show **(healthy)** |
| http://localhost:10000 | Welcome page works |
| Merge compose to `main` | **No Render impact** |

---

*See also: [why this version](./01-why-this-version.md) · [Render production](./02-production-render.md)*
