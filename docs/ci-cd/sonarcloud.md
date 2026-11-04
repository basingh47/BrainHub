# SonarCloud

## Purpose

SonarCloud is a cloud-based static code quality platform that continuously analyzes the project for:

- Bugs
- Security vulnerabilities
- Security hotspots
- Code smells
- Duplicated code
- Maintainability issues
- Test coverage (via PHPUnit coverage reports)

It provides an additional quality gate beyond PHPUnit and Larastan.

---

## Current Integration

SonarCloud runs as a step inside the GitHub Actions `ci` job, after the tests (it needs `coverage.xml`). It runs on every PR and on every push to `main` or `develop`.

```
PR / push to main / push to develop → GitHub Actions (ci)
                 ├── Build assets
                 ├── Laravel Pint
                 ├── Larastan
                 ├── PHPUnit  → coverage.xml
                 ├── Composer Audit
                 └── SonarCloud Scan
```

| Item | Where |
|---|---|
| Workflow step | `.github/workflows/ci.yml` → `SonarSource/sonarqube-scan-action@v8` |
| Project settings | `sonar-project.properties` (organization `basingh47-1`, key `basingh47_BrainHub`, sources `app`, tests `tests`, coverage `coverage.xml`) |
| Auth | `SONAR_TOKEN` repository secret (GitHub → Settings → Secrets → Actions) |
| Blocking? | Yes — a failed scan (e.g. invalid token) fails the `ci` check |

The action was updated on 2026-07-29 from `@v4` (flagged by GitHub as unsupported) to `@v8`.

---

## Current Project Status

| Item | Status |
|---|---|
| GitHub repository | Public |
| SonarCloud project | Public (free plan: branch and pull request analysis included) |
| Pull requests | ✅ Analyzed on every PR |
| `main` | ✅ Re-analyzed after every merge (CI runs on pushes to `main`) |

### `SONAR_TOKEN`

The scan authenticates with a **personal access token** stored as the `SONAR_TOKEN` repository secret. Personal tokens expire.

**Symptom of an expired or missing token** — the SonarCloud scan step fails with:

```
ERROR Failed to query JRE metadata: ... failed with HTTP 403 Forbidden.
Please check the property sonar.token or the environment variable SONAR_TOKEN.
```

**Renew it:**

1. https://sonarcloud.io/account/security → **Personal Tokens** → name `github-actions-brainhub`, longest expiry → **Generate**, copy it.
2. https://github.com/basingh47/BrainHub/settings/secrets/actions → update `SONAR_TOKEN`.
3. Re-run the failed CI run.

History: scans silently failed from July to November 2026 because the token had expired and the step was `continue-on-error`. Fixed on 4 November 2026 (new token, step made blocking, result added to the CI summary).

---

## SonarCloud UI — where to see results

Project: https://sonarcloud.io/project/overview?id=basingh47_BrainHub

SonarCloud keeps a **separate result for each place a scan comes from**. Where you look depends on what triggered CI:

| CI ran on | Result appears in | What it reports |
|---|---|---|
| A pull request | Left menu → **Project → Pull requests** → `#<number>` | Only **new** issues added by that PR, plus its Quality Gate |
| A push to `main` (a merged PR) | **Overview** page (default view, branch `main`) | The **whole project**: all issues, ratings, Quality Gate, lines of code |
| A push to `develop` | Left menu → **Project → Branches** → `develop` | Whole project, on `develop` |

```
Open PR ──CI──▶ SonarCloud: Pull requests → #N      (Overview unchanged)
Merge PR ──CI on main──▶ SonarCloud: Overview        (updated ~2 min after merge)
```

A green PR check does **not** change the Overview. The Overview only updates after the PR is merged and CI finishes on `main`.

### Main pages

| Page (left menu) | Use it for |
|---|---|
| **Overview** | Health of `main`: Quality Gate, issue count, duplication, coverage, security rating, and "Last analysis" date |
| **Analysis → Issues** | Full list of bugs, vulnerabilities and code smells, with file and line. Filter by branch or PR at the top |
| **Analysis → Security Hotspots** | *Deprecated by SonarCloud* — hotspot findings now appear under **Issues** |
| **Project → Pull requests** | Result of each PR scan |
| **Project → Branches** | Results for branches other than `main` |
| **Reporting → Activity** | History of analyses (check here that a scan actually arrived) |
| **Policies → Quality gate** | The pass/fail rules applied to new code |

### Reading the Overview

| You see | Meaning |
|---|---|
| **Last analysis X ago** | When `main` was last scanned. If it is older than the last merge, the scan on `main` failed — check the CI run on `main` |
| **Quality gate: Not computed** | No CI analysis of `main` has completed yet |
| **Quality gate: Passed / Failed** | Whether `main` meets the Quality Gate rules |
| **Lines of code** | Size of what is scanned (`app/` only, per `sonar-project.properties`) |
| **Coverage: No data** | `coverage.xml` was not picked up by the scan |

### Why a PR can show 0 issues

Only files under `app/` are scanned (`sonar.sources=app`). A PR that changes only docs, CI, config or views reports no new issues.

---

## Why SonarCloud Is Used

Unlike PHPUnit, SonarCloud looks for long-term code quality issues such as:

- Complex methods
- Duplicate logic
- Potential bugs
- Maintainability problems
- Security concerns

This helps improve the overall health of the codebase over time.

---

## Future Improvements

- Enforce the Sonar Quality Gate before merging

---

## Notes

Current setup is intended for learning and personal development.

GitHub Actions already provides:

- Laravel Pint
- Larastan
- PHPUnit
- Composer Audit

SonarCloud acts as an additional quality analysis tool on top of these.

---

*Last updated: 4 November 2026*
