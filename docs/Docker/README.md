# Docker documentation

BrainHub Docker setup is versioned as features are added.
Each folder explains **what that version supports** and **why it exists**.

| Version | Folder | Status | Supports |
|---|---|---|---|
| v1 | [Docker-v1](./Docker-v1/) | Historical | Welcome page — simple PHP-only `Dockerfile` |
| **v2** | [Docker-v2](./Docker-v2/) | **Current** | Breeze auth — multi-stage `Dockerfile` with Vite asset build |

**Source files in the repo root:**

- [`Dockerfile`](../../Dockerfile) — **v2** production app image (Render builds this)
- [`docker-compose.yml`](../../docker-compose.yml) — local only (Render ignores it)
- [`.dockerignore`](../../.dockerignore)

**Start here (current):** [Docker-v2 → README](./Docker-v2/README.md)  
**Why we left v1:** [Docker-v2 → problem](./Docker-v2/01-problem-why-v1-not-enough.md)
