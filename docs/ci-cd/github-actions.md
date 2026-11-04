# ⚙️ BrainHub CI/CD Pipeline

Defined in [.github/workflows/ci.yml](../../.github/workflows/ci.yml). This doc explains what the pipeline does and how to fix common failures, so recurring issues don't need to be re-diagnosed from scratch.

---

## Triggers

- Pull request targeting `main` or `develop`
- Push to `main` or `develop`
- Manual run (`workflow_dispatch`, from the Actions tab)

Pushes to `main` happen only when a PR is merged. CI runs again on the merged code so SonarCloud re-analyzes `main` (the project Overview page).

The whole workflow is one job named `ci`. That job is the required status check on `main` (see [branch protection](../git-workflow/branch-protection.md)).

## Steps (in order)

| # | Step | Command / file | Blocking |
|---|---|---|---|
| 1 | Checkout (full history, for SonarCloud) | `actions/checkout` | — |
| 2 | Set up PHP 8.4 + extensions | `shivammathur/setup-php` | — |
| 3 | Install Composer dependencies (cached by `composer.lock` hash) | `composer install` | ✅ |
| 4 | Set up Node (version from `.nvmrc`) | `actions/setup-node` | — |
| 5 | Build frontend assets | `npm ci && npm run build` | ✅ |
| 6 | Bootstrap `.env` / `APP_KEY` | `cp .env.example .env && php artisan key:generate` | ✅ |
| 7 | Code style | `vendor/bin/pint --test` | ✅ |
| 8 | Static analysis (config: `phpstan.neon`) | `vendor/bin/phpstan analyse` | ✅ |
| 9 | Tests + coverage (config: `phpunit.xml`) | `vendor/bin/phpunit --coverage-clover=coverage.xml` | ✅ |
| 10 | Dependency security | `composer audit` | ✅ |
| 11 | SonarCloud scan (config: `sonar-project.properties`) | `SonarSource/sonarqube-scan-action` | ✅ |
| 12 | Pipeline summary | written to the run's Summary tab | — |

Each blocking check has an "Explain … failure" step that prints the cause and the fix command in the run log. Coverage and audit reports are uploaded as run artifacts.

Step 5 is needed because Breeze pages use `@vite`; without `public/build/manifest.json` the auth tests return HTTP 500.

---

## Troubleshooting

### `composer audit` fails with security advisories (e.g. guzzlehttp/guzzle, guzzlehttp/psr7)

**What it means:** `composer audit` checks the exact versions pinned in `composer.lock` against a public vulnerability database (Packagist advisories / GitHub Security Advisories). A failure here means one of your locked package versions falls inside a range that has a disclosed advisory — it does **not** mean Laravel itself is outdated, and it does **not** mean a package is simply "old." A package can be years out of date and still pass audit if no advisory has ever been filed against the version you're on.

**Why it happens:** `composer.lock` freezes exact versions at the moment it's generated and never moves forward on its own. Advisories get published continuously, often well after a version was locked in — so a lock file that was clean when written can start failing later purely because time passed and a new advisory now applies to a version you already had, with no code change on your side.

Example encountered on 2026-07-28:
- `guzzlehttp/guzzle` locked at `7.12.1`, multiple advisories affecting `<7.15.1` (cookie/referrer leaks, DoS via unbounded cookies, CVE-2026-59883).
- `guzzlehttp/psr7` locked at `2.12.1`, CVE-2026-59882 affecting `<2.12.3`.
- Both are transitive dependencies pulled in by `laravel/framework` (constraint `^7.8.2` on Guzzle), not direct dependencies — Laravel didn't need upgrading, only the lock file did.

Example encountered on 2026-11-04:
- `laravel/framework`, `league/commonmark`, `league/flysystem`, `symfony/http-foundation`, `symfony/mime` had new advisories.
- Fixed with the command below; this also moved Laravel 13.16 → 13.40 and Guzzle 7 → 8.

**How to fix:**
```bash
composer update <package1> <package2> --with-all-dependencies
```
This re-resolves only the named packages (plus anything that must move with them) to the newest version still allowed by whatever requires them — no need to touch `composer.json` unless the fix requires a version outside the existing constraint. Commit the updated `composer.lock`.

**How to reduce recurrence:**
- Add Dependabot (or Renovate) to auto-open PRs when a patched version becomes available, instead of finding out via a failed CI run.
- Periodically run `composer update` rather than letting the lock file go stale indefinitely.
- Keep `composer audit` as a **blocking** step (don't add `continue-on-error`) — it's the only thing forcing dependency hygiene; pair it with Dependabot so it rarely fires as a surprise.

---

*Last updated: 4 November 2026*
