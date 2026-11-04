# Why Docker v1

## What this version is for

Docker **v1** matches the **welcome-page** stage of BrainHub:

1. **Production** — Render builds the `Dockerfile`; database on Aiven.
2. **Local learning** — Docker Desktop runs app + MySQL via `docker-compose.yml`.

One idea at a time: ship a simple app container, connect to MySQL outside that container.

---

## Why we need a Dockerfile

Render does not use Laragon. It needs a recipe for:

- PHP version
- Extensions (`pdo_mysql`, `zip`)
- Composer install
- Start command and port (`$PORT`)

That recipe is the **`Dockerfile`**.

---

## Why v1 stays simple

**v1 goals:**

1. Simple production image for the welcome page.
2. Learn: one container = one main process (the Laravel app).
3. Learn: database is **outside** the app image (Aiven in prod, MySQL service locally).
4. Local `docker-compose.yml` to practice app + MySQL — **without** changing Render.

**v1 does not include:** MySQL inside the app image, or replacing Laragon for daily coding.

---

## Why we created `docker-compose.yml`

| Question | Answer |
|---|---|
| Why create it? | Start **app + MySQL** locally with one command; learn `DB_HOST=mysql`. |
| Does Render read it? | **No.** Merging it to `main` does not change production. |
| Need a `.env` for Compose? | **No** in v1 — values are inline in the YAML. |

```
Dockerfile     = build the APP image
Compose        = run APP + MySQL together (local)
Platform env   = secrets and DB URL (Render / Aiven)
```

---

## How app and MySQL communicate

Same pattern locally and on Render — only the host changes:

| Environment | `DB_HOST` | Network |
|---|---|---|
| Local Compose | `mysql` | Docker network |
| Render | Aiven hostname | Internet |

---

## Next Docker version

**Done:** [Docker-v2](../Docker-v2/) documents why v1 is not enough for Breeze and updates the Dockerfile (Vite asset build).

v1 docs stay about the welcome-page setup only.

---

## Day to day with v1

| Goal | Tool |
|---|---|
| Write Laravel code | Laragon |
| Smoke-test containers | `docker compose build` + `docker compose up` |
| Deploy welcome page | Render builds **`Dockerfile`** → Aiven MySQL |

---

*Docker-v1 — August 2026*
