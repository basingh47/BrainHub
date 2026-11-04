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

SonarCloud runs as a step inside the GitHub Actions `ci` job, after the tests (it needs `coverage.xml`).

```
Pull Request → GitHub Actions (ci)
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
| `main` | Not re-analyzed after merge (CI does not run on pushes to `main`) |

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

## Reports Available

The SonarCloud dashboard shows, per branch and pull request:

- Bugs, Vulnerabilities, Security Hotspots, Code Smells
- Maintainability, Reliability and Security ratings
- Code Duplication
- Test Coverage (from `coverage.xml`)

Open the project at https://sonarcloud.io/project/overview?id=basingh47_BrainHub.

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

- Run CI on pushes to `main` so `main` is analyzed after each merge
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
