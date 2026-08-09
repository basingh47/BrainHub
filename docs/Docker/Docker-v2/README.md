# Docker v2 — Breeze (authentication)

**Status:** Current (replaces v1 for deploying Breeze)  
**Date:** August 2026  
**App scope:** Laravel Breeze — login, register, password reset, email verify, profile, dashboard  

---

## Documents in this folder

| Doc | What it covers |
|---|---|
| [01-problem-why-v1-not-enough.md](./01-problem-why-v1-not-enough.md) | Why Docker-v1 cannot run Breeze |
| [02-solution-dockerfile.md](./02-solution-dockerfile.md) | What we changed in the `Dockerfile` and why |
| [03-production-and-local.md](./03-production-and-local.md) | Render + local Compose with v2 |

---

## Short answer

| Version | Good for |
|---|---|
| [Docker-v1](../Docker-v1/) | Welcome page only |
| **Docker-v2 (this)** | Breeze auth pages (needs CSS/JS build in the image) |

**Problem:** Breeze layouts call `@vite` and need `public/build/`.  
**v1 Dockerfile** never runs `npm run build`, so auth pages fail.  
**v2** adds a Node build stage and copies compiled assets into the final image.

---

## Quick picture

```
v1:  PHP only  →  welcome OK, Breeze broken

v2:  Stage vendor (Composer)
     Stage assets (Node → npm run build)
     Stage final (PHP + vendor + public/build)  →  Breeze OK
```

Local Compose still works the same: `docker compose build` / `up` — it rebuilds using the updated `Dockerfile`.
