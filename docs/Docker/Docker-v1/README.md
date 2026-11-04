# Docker v1 — welcome page

**Status:** Historical (kept for learning)  
**Date:** August 2026  
**App scope:** Welcome page (`/`) only  

Simple PHP-only Dockerfile. **Not suitable for Breeze** — see [Docker-v2](../Docker-v2/) (current).

---

## Documents in this folder

| Doc | What it covers |
|---|---|
| [01-why-this-version.md](./01-why-this-version.md) | Why v1 exists; when we will create the next Docker version |
| [02-production-render.md](./02-production-render.md) | Render + `Dockerfile` + Aiven MySQL |
| [03-local-compose.md](./03-local-compose.md) | Why `docker-compose.yml`; how app ↔ MySQL talk |

---

## Quick picture

```
PRODUCTION (Render)          LOCAL (Docker Desktop)
─────────────────────        ────────────────────────────
Dockerfile → app             Dockerfile → app container
Aiven MySQL (external)       docker-compose.yml → mysql container
Render env vars              Compose inline environment
compose.yml NOT used         compose.yml IS used
```

---

## Files this version uses

| File | Production? | Local Docker? |
|---|---|---|
| `Dockerfile` | Yes | Yes (Compose builds it) |
| `docker-compose.yml` | No | Yes |
| `.dockerignore` | Yes | Yes |
| Project `.env` | No (Render dashboard) | Not required for Compose |

---

## What v1 is for

| Check | Result |
|---|---|
| `/` welcome page | Works |
| App ↔ MySQL (local Compose) | Works when containers are up |
| App ↔ Aiven (Render) | Works when Render `DB_*` env is set |

**Next:** [Docker-v2](../Docker-v2/) — problem (v1 + Breeze) and updated Dockerfile.
