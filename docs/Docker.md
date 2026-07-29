# 🐳 BrainHub Docker Image (Render deployment)

Defined in [Dockerfile](../Dockerfile) and [.dockerignore](../.dockerignore). This doc explains what the image does, why each decision was made, and how to fix common failures.

> ### ⚠️ Scope: this image is for Render only — **not** for local development
>
> Nothing in this file is used when you work on your machine. Local development runs directly on your host PHP and Node:
>
> ```bash
> nvm use                 # Node 22, per .nvmrc
> composer install        # includes dev packages (Breeze, Pint, Larastan, PHPUnit)
> npm install && npm run build
> php artisan serve
> ```
>
> Do **not** try to develop inside this image. It is built with `composer install --no-dev`, so Pint, Larastan, PHPUnit and Breeze's `breeze:install` command are all absent, and it contains no Node at all. It exists purely to produce the artifact Render runs.

---

## Why the image has three stages

```
Stage 1  "vendor"   php:8.4-cli    → installs PHP dependencies
Stage 2  "assets"   node:22-alpine → compiles css/js, then is DISCARDED
Stage 3  (final)    php:8.4-cli    → the image Render actually runs
```

Only stage 3 ships. Stages 1 and 2 exist to produce files that get copied forward, then are thrown away — so the shipped image contains **no Node, no `node_modules`, and no dev dependencies**.

Stage 1 and stage 3 begin with a byte-identical `apt-get` instruction on the same base image, so Docker builds that layer once and reuses it. The extra stage costs no build time.

---

## Stage 2 in detail: why Node is required at all

**Node is a build-time compiler, not a runtime service.** It is the same relationship Composer has to PHP: you need it to produce the output, not to run the output. Nothing Node-related is alive when a user visits the site.

It is required because the source files in `resources/` are not browser-readable:

| File | Source | After `npm run build` |
|---|---|---|
| `resources/css/app.css` | 59 bytes — `@tailwind base;` … | ~35 kB of real CSS |
| `resources/js/app.js` | `import Alpine from 'alpinejs'` | ~45 kB with Alpine bundled in |

A browser has no idea what `@tailwind base;` means, and it cannot resolve the bare package name `alpinejs` — it has no `node_modules` to look in. Vite reads those instructions, pulls Alpine out of `node_modules`, scans the Blade files for which Tailwind classes are actually used, and writes real CSS and JS into `public/build/`.

### Why this is mandatory, not optional

`public/build/` is gitignored ([.gitignore:17](../.gitignore#L17)) and untracked, so compiled assets exist in neither the repo nor the build context. If the image does not build them, `@vite` cannot find its manifest and throws:

```
Vite manifest not found at: /var/www/html/public/build/manifest.json
```

That is an **unrecoverable exception — HTTP 500**, not a styling glitch. Verified locally by hiding the manifest:

```
/       -> HTTP 200     ← welcome page has its own inline-CSS fallback
/login  -> HTTP 500     ← at resources/views/layouts/guest.blade.php:15
```

### Why `main` worked before this was fixed

Laravel's default `welcome.blade.php` guards the call:

```blade
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    <style>/*! tailwindcss v4.0.7 ... */</style>   ← ~38 kB inlined
@endif
```

The skeleton ships that fallback so a fresh install looks right before anyone runs npm. Breeze's layouts (`layouts/app.blade.php:15`, `layouts/guest.blade.php:15`) call `@vite` **unguarded**, so they have no such safety net. The site was fine only because the one page it served carried its own CSS.

### Why the pagination views are copied in

`tailwind.config.js` scans three paths for class names:

```js
content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
],
```

The first lives in `vendor/`, which `.dockerignore` excludes — hence stage 1 exists, so stage 2 can copy just that directory across. Without it the pagination component ships unstyled.

The second path (`storage/framework/views`) is **compiled Blade output and is always empty in a fresh image**. That is correct and intentional. Measured effect of each glob:

| Globs present | CSS output |
|---|---|
| `resources/views` only | 33.00 kB |
| \+ pagination | **35.47 kB** ← what this image produces |
| \+ `storage/framework/views` | 45.38 kB |
| all three (a local dev build) | 47.84 kB |

A local build looks ~12 kB larger, but that difference is **not missing styles**. 42 of the 70 compiled views on a working dev machine are Laravel's debug exception-renderer templates, compiled because an error was rendered at some point:

```
vendor/laravel/framework/.../exceptions/renderer/components/*.blade.php
vendor/laravel/framework/.../Foundation/Exceptions/views/minimal.blade.php
```

That CSS styles debug error pages, which never render in production (`APP_DEBUG=false`). It also makes local builds non-reproducible — a fresh clone that never errored produces a smaller file. The image's 35.47 kB is the correct deterministic output.

---

## Stage 3 in detail

### Dependency install order

```dockerfile
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --no-dev && php artisan package:discover --ansi
```

Manifests are copied **before** application code so Docker reuses the install layer until dependencies actually change — otherwise editing one controller re-downloads every package. The autoloader is deferred with `--no-autoloader` and generated afterwards, once the app code it must map is present. `--no-scripts` prevents artisan from running before that code exists.

`--no-dev` is what excludes `laravel/breeze`, `pint`, `larastan` and `phpunit`. Breeze is a **code generator**, not a runtime library — it copied controllers, views and routes into the repo during `breeze:install` and has no runtime code path, so the production image does not need it. See [progress.md](progress.md) for the auth feature itself.

### `vendor/` and `COPY . .` do not conflict

`vendor` is listed in `.dockerignore`, so `COPY . .` cannot clobber the `vendor/` copied from stage 1.

### opcache

```dockerfile
docker-php-ext-install pdo_mysql zip opcache
```

`opcache` is not enabled by default in the official PHP images. It caches compiled PHP bytecode in memory so source files are not recompiled on every request — the single largest free performance win for production PHP. `opcache.validate_timestamps=0` skips file-modification checks entirely, which is safe because the code in an image never changes; it also means **a code change requires a new deploy, never a hot edit**.

`pdo_mysql` is required to reach the database (`DB_CONNECTION=mysql`); without it every query fails.

### Why caching and migrations run in `CMD`, not `RUN`

```dockerfile
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
```

**This is the most important subtlety in the file.** `.env` is excluded by `.dockerignore`, and Render's environment variables do not exist during `docker build` — they are injected at container start. Running `config:cache` as a build step would read empty values and **bake blank credentials permanently into the image**. It must happen at startup.

`migrate --force` creates the `users`, `sessions` and `password_reset_tokens` tables. `--force` skips the interactive confirmation, which is required in a non-TTY container. It only applies *pending* migrations, so it is a safe no-op if they already ran.

> **Caveat:** with more than one instance, several containers would race to migrate on startup. If this service is ever scaled beyond one instance, move `migrate --force` out of `CMD` and into a Render pre-deploy command.

### `php artisan serve` — a known, deliberate compromise

`php artisan serve` is a **development server**. It is kept for now because replacing it is a separate, larger change, and because the asset gap above was the thing actually breaking pages — this only degrades performance.

The problem is that it handles one request at a time. Six simultaneous requests to `/login`, measured locally:

```
req4: 0.028s
req6: 0.040s   ← +12ms
req3: 0.053s   ← +13ms
req1: 0.065s   ← +12ms
req5: 0.074s   ← +9ms
req2: 0.082s   ← +8ms
```

Each completes ~12 ms after the previous. That staircase is a queue, not concurrency. Scaled to realistic page costs:

| Page cost | 6th visitor waits |
|---|---|
| 12 ms | 0.08 s |
| 300 ms (real DB + network) | 1.8 s |
| 2 s (SMTP handshake) | **12 s** |

The last row is real for this app: Laravel's `ResetPassword` notification is not queued by default, so a password-reset request holds the worker for the entire SMTP round-trip while everyone else waits.

**Mitigation in place:** `ENV PHP_CLI_SERVER_WORKERS=4` forks four request handlers instead of one. Laravel's `serve` command honours this. It genuinely helps, but the PHP manual still states the built-in server is "not intended to be used on a public network."

**Proper fixes, when there is time:**
- `FROM php:8.4-apache` — Apache + mod_php, multi-process, same official image family. Needs `DocumentRoot` set to `public/`, `mod_rewrite` enabled, and a runtime substitution of `$PORT` into Apache's config (Render injects the port, Apache reads it from a static file).
- `dunglas/frankenphp` — single binary, HTTP/2, plus an optional worker mode that keeps Laravel booted between requests. Faster, but a larger change.

---

## Files that must be committed

The build copies these. Render builds from the git repo, so an **untracked** file is simply absent and the build fails:

| File | If missing |
|---|---|
| `package-lock.json` | `npm ci` fails outright |
| `tailwind.config.js` | no content globs — styling collapses |
| `postcss.config.js` | Tailwind never runs |
| `composer.lock` | `composer install` cannot resolve |

All four arrived with `breeze:install`. Confirm before deploying:

```bash
git ls-files package-lock.json tailwind.config.js postcss.config.js composer.lock
```

Four lines of output means you are safe.

---

## Required Render environment variables

`.env` is never in the image, so every value comes from Render's dashboard.

| Variable | Value | Note |
|---|---|---|
| `APP_KEY` | `base64:…` | from `php artisan key:generate --show` |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | **critical** — see below |
| `APP_URL` | your Render URL | wrong value breaks signed verification links |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | | |
| `MAIL_MAILER` | a real driver | **not `log`** — see below |
| `MAIL_FROM_ADDRESS` | address on a domain you control | |
| `SESSION_SECURE_COOKIE` | `true` | Render terminates TLS |
| `PHP_CLI_SERVER_WORKERS` | `4` | already set in the image |

### `APP_DEBUG=false` is not optional

With `APP_DEBUG=true`, any error renders a full stack trace including environment variables and executed SQL. During testing, the manifest-not-found 500 exposed the app's `mysql` session query in the response body. Both `.env` and `.env.example` currently ship `APP_DEBUG=true`.

### `MAIL_MAILER=log` silently breaks auth

Both `.env` and `.env.example` set `MAIL_MAILER=log`, which writes mail to `storage/logs/laravel.log` instead of sending it. Password reset then *appears* to work — the user sees "We have emailed your password reset link!" and nothing arrives. A real driver (Resend, Postmark, SES, Mailgun) is required before the auth feature is usable.

---

## Troubleshooting

### Build fails at `npm ci` with `Cannot read ... package-lock.json`

`package-lock.json` is not committed. See *Files that must be committed*.

### Every page returns 500, `Vite manifest not found`

Stage 2 did not run or its output was not copied. Check that `COPY --from=assets /app/public/build ./public/build` is present, and that `tailwind.config.js` and `postcss.config.js` are committed.

### Pages render but are completely unstyled

`postcss.config.js` is missing from the build context, so Tailwind never processed `app.css`. The `@tailwind` directives pass through as invalid CSS and the browser discards them.

### App boots but every DB query fails

`pdo_mysql` missing from `docker-php-ext-install`, or the `DB_*` env vars are not set in Render.

### Config changes in Render's dashboard have no effect

`config:cache` runs at container start, so cached config is written once per boot. Restart the service after changing environment variables.

### Deploy succeeds but the container exits immediately

`CMD` is the container's main process — when it exits, the container stops. If any command in the `&&` chain fails (a failing migration, `config:cache` erroring on a missing `APP_KEY`), `php artisan serve` never starts and Render crash-loops. Check the deploy logs for which link in the chain failed.

---

## Known limitations

1. **`php artisan serve` is a dev server** — see above. Highest-value remaining improvement.
2. **`migrate --force` on startup does not tolerate multiple instances.**
3. **No health check** — Render cannot distinguish "booting" from "wedged".
4. **No queue worker.** `QUEUE_CONNECTION=database` is set but nothing runs `queue:work`. Harmless today because notifications send synchronously; anything queued later would silently accumulate in the `jobs` table.
5. **PHP version drift.** The image and CI both use PHP 8.4; local development is on 8.3.21. `composer.json` requires `^8.3`, so all three are valid, but production is not the version used for local testing.
6. **Tailwind 3, not 4.** `breeze:install` downgraded `tailwindcss` from `^4.0.0` to `^3.1.0` and left `@tailwindcss/vite@^4.0.0` orphaned in `package.json`, imported by nothing. Unrelated to this image, but it is what the build compiles.

---

## Verification status

The image itself has **not been built and run** — the Docker daemon was unavailable when this was written. Stage 2 was verified by replicating its exact `COPY` list into a clean directory and running the build:

```
npm ci        → 0 vulnerabilities
npm run build → ✓ built in 624ms, manifest.json + app.css + app.js produced
```

Stages 1 and 3 are unverified. **The first Render deploy after these changes is the real test** — watch the build log through `npm ci`, `composer install`, and the `CMD` chain.

---

*Last updated: 29 July 2026*
