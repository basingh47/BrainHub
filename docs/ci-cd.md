# ⚙️ BrainHub CI/CD Pipeline

Defined in [.github/workflows/ci.yml](../.github/workflows/ci.yml). This doc explains what the pipeline does and how to fix common failures, so recurring issues don't need to be re-diagnosed from scratch.

---

## Triggers

- Push to `develop`
- Pull request targeting `main`
- Manual run (`workflow_dispatch`, from the Actions tab)

## Steps (in order)

1. Checkout code (full history — needed for SonarCloud).
2. Set up PHP 8.4.
3. Install Composer dependencies (cached by `composer.lock` hash).
4. Bootstrap `.env` / `APP_KEY`.
5. `vendor/bin/pint --test` — code style check.
6. `vendor/bin/phpstan analyse` — static analysis (Larastan).
7. `vendor/bin/phpunit --coverage-clover=coverage.xml` — test suite.
8. `composer audit` — dependency security scan.
9. SonarCloud scan (`continue-on-error: true` until `SONAR_TOKEN` is configured).

Any of steps 5–8 failing fails the whole job (step 9 is the only non-blocking one).

---

## Troubleshooting

### `composer audit` fails with security advisories (e.g. guzzlehttp/guzzle, guzzlehttp/psr7)

**What it means:** `composer audit` checks the exact versions pinned in `composer.lock` against a public vulnerability database (Packagist advisories / GitHub Security Advisories). A failure here means one of your locked package versions falls inside a range that has a disclosed advisory — it does **not** mean Laravel itself is outdated, and it does **not** mean a package is simply "old." A package can be years out of date and still pass audit if no advisory has ever been filed against the version you're on.

**Why it happens:** `composer.lock` freezes exact versions at the moment it's generated and never moves forward on its own. Advisories get published continuously, often well after a version was locked in — so a lock file that was clean when written can start failing later purely because time passed and a new advisory now applies to a version you already had, with no code change on your side.

Example encountered on 2026-07-28:
- `guzzlehttp/guzzle` locked at `7.12.1`, multiple advisories affecting `<7.15.1` (cookie/referrer leaks, DoS via unbounded cookies, CVE-2026-59883).
- `guzzlehttp/psr7` locked at `2.12.1`, CVE-2026-59882 affecting `<2.12.3`.
- Both are transitive dependencies pulled in by `laravel/framework` (constraint `^7.8.2` on Guzzle), not direct dependencies — Laravel didn't need upgrading, only the lock file did.

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

*Last updated: 28 July 2026*
