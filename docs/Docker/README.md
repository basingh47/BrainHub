# Docker documentation

BrainHub Docker setup is versioned as features are added.
Each folder explains **what that version supports** and **why it exists**.

| Version | Folder | Status | Supports |
|---|---|---|---|
| v1 | [Docker-v1](./Docker-v1/) | Historical | Welcome page — simple PHP-only `Dockerfile` |
| v2 | [Docker-v2](./Docker-v2/) | Historical | Breeze auth — multi-stage `Dockerfile` with Vite asset build |
| **v3** | [Docker-v3](./Docker-v3/) | **Current** | NGINX + PHP-FPM (`serversideup/php`) — production web server |

**Source files in the repo root:**

- [`Dockerfile`](../../Dockerfile) — **v3** production app image (Render builds this)
- [`docker-compose.yml`](../../docker-compose.yml) — local only (Render ignores it)
- [`.dockerignore`](../../.dockerignore)

**Start here (current):** [Docker-v3 → README](./Docker-v3/README.md)  
**Why we left v2:** [Docker-v3 → problem](./Docker-v3/01-problem-why-v2-not-enough.md)  
**Why we left v1:** [Docker-v2 → problem](./Docker-v2/01-problem-why-v1-not-enough.md)
