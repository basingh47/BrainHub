# Solution: Docker-v2 Dockerfile

## What we changed

Docker-v2 keeps **one** `Dockerfile` for Render (and for local Compose `build: .`).

It becomes **three stages**:

```
Stage 1  vendor   php:8.4-cli     → Composer PHP packages
Stage 2  assets   node:22-alpine  → npm ci && npm run build  (then discarded)
Stage 3  final    php:8.4-cli     → app + vendor + public/build  (what Render runs)
```

Only stage 3 is shipped. Node does not run in production — it only builds files during `docker build`.

---

## Why three stages

| Stage | Why |
|---|---|
| **vendor** | Install PHP deps; also lets the assets stage copy Laravel pagination Blade views that `tailwind.config.js` scans |
| **assets** | Compile Tailwind/Alpine into `public/build` — required for Breeze `@vite` |
| **final** | Lean PHP runtime + code + compiled assets |

---

## Important details

### `public/build` is not in Git

It is gitignored. The image **must** create it during build, or Breeze pages fail.

### Files that must be committed (for the asset stage)

| File | If missing |
|---|---|
| `package-lock.json` | `npm ci` fails |
| `tailwind.config.js` | styles wrong / incomplete |
| `postcss.config.js` | Tailwind may not run |
| `composer.lock` | Composer resolve issues |

### Pagination views

`tailwind.config.js` includes:

```js
'./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php'
```

`vendor/` is in `.dockerignore`, so stage 1 installs vendor, then stage 2 copies only that pagination path for the Tailwind scan.

### Still not in the app image

- MySQL (Aiven / Compose `mysql`)
- Secrets (Render env / Compose `environment`)
- Node / `node_modules` at runtime

---

## What stayed the same as v1

| Same | Meaning |
|---|---|
| `pdo_mysql` | Talk to Aiven / Compose MySQL |
| `composer install --no-dev` | No Pint/Breeze package at runtime (Breeze already generated code into the repo) |
| `php artisan serve` + `$PORT` | Render port binding |
| Compose local-only | Render ignores `docker-compose.yml` |

---

## Rebuild after this change

```powershell
docker compose build --no-cache
docker compose up
```

Or on Render: push → new image build automatically.

Verify: http://localhost:10000/login should load (not 500).

---

Next: [03-production-and-local.md](./03-production-and-local.md)
