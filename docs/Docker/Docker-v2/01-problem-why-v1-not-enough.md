# Problem: why Docker-v1 is not suitable for Breeze

## What changed in the app

Docker-v1 was built for the **welcome page** stage.

We then added **Laravel Breeze** (authentication):

- Login / register / logout  
- Password reset / email verification  
- Profile / dashboard  
- Blade layouts styled with **Tailwind CSS** + **Alpine.js** via **Vite**

The app code is ready. The **v1 Docker image is not**.

---

## What Docker-v1 does

```
FROM php:8.4-cli
→ install pdo_mysql, zip, Composer
→ COPY app
→ composer install --no-dev
→ php artisan serve
```

No Node. No `npm install`. No `npm run build`.  
`public/build/` is **not** in the image (`public/build` is gitignored).

---

## Why the welcome page worked on v1

`welcome.blade.php` is special: if Vite assets are missing, it can fall back to **inline CSS**.

So Render/local Docker with v1 could still show `/`.

---

## Why Breeze breaks on v1

Breeze layouts (for example `resources/views/layouts/guest.blade.php` and `layouts/app.blade.php`) always call:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

There is **no** “missing assets” fallback.

Without `public/build/manifest.json` Laravel throws:

```text
Vite manifest not found
```

Result: auth pages return **HTTP 500** (not “unstyled” — they crash).

| Page | Docker-v1 |
|---|---|
| `/` welcome | Works |
| `/login`, `/register`, `/dashboard`, … | **Fails** |

---

## Root cause (one sentence)

**Breeze needs compiled frontend assets in the container; Docker-v1 only ships PHP.**

```
Source (not enough for browsers)     After npm run build
───────────────────────────────      ───────────────────
resources/css/app.css  (@tailwind) → public/build/assets/*.css
resources/js/app.js    (Alpine)    → public/build/assets/*.js
                                   → public/build/manifest.json
```

Node is a **build-time** tool here — same idea as Composer: needed to produce files, not to stay running in production.

---

## What we must change (Docker-v2)

| Need | Change |
|---|---|
| Build CSS/JS in CI/image | Add a **Node** stage: `npm ci` + `npm run build` |
| Ship assets with the app | `COPY` `public/build` into the final PHP image |
| Keep MySQL outside the app | Unchanged — Aiven (Render) / Compose `mysql` (local) |
| Keep Compose local-only | Unchanged — Render still ignores `docker-compose.yml` |

We do **not** put MySQL inside the Dockerfile.  
We **do** extend the Dockerfile so the app image includes Vite output.

---

## Why a new version folder (v2)

So history stays clear during the project:

- **v1** = welcome page Docker (still documented)  
- **v2** = why v1 failed for Breeze + how we fixed the image  

Next: [02-solution-dockerfile.md](./02-solution-dockerfile.md)
