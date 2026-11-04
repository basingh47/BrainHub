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
                 └── SonarCloud Scan (non-blocking)
```

| Item | Where |
|---|---|
| Workflow step | `.github/workflows/ci.yml` → `SonarSource/sonarqube-scan-action@v8` |
| Project settings | `sonar-project.properties` (organization `basingh47-1`, key `basingh47_BrainHub`, sources `app`, tests `tests`, coverage `coverage.xml`) |
| Auth | `SONAR_TOKEN` repository secret (GitHub → Settings → Secrets → Actions) |
| Blocking? | No — `continue-on-error: true`, so a failed scan does not fail CI |

The action was updated on 2026-07-29 from `@v4` (flagged by GitHub as unsupported) to `@v8`.

---

## Current Project Status

| Item | Status |
|---|---|
| GitHub repository | Public |
| SonarCloud project | Public (free plan: branch and pull request analysis included) |
| Last analysis | **10 July 2026** (`main`, and PR #2) |
| Later PRs (#4, #6) | ❌ Not analyzed |

Scans stopped after July. Because the step is non-blocking, CI stays green and the failure is easy to miss. To find the cause, open a recent CI run → **SonarCloud scan** step log. Common causes:

- `SONAR_TOKEN` secret missing or expired.
- SonarCloud **Automatic Analysis** is enabled for the project, which conflicts with CI-based analysis (SonarCloud → Administration → Analysis Method → turn it off).

Also note: CI does not run on pushes to `main`, so `main` is not re-analyzed after a merge. Adding `main` to the workflow's `push:` branches would fix that.

---

## Reports Available

The SonarCloud dashboard shows, per branch and pull request:

- Bugs, Vulnerabilities, Security Hotspots, Code Smells
- Maintainability, Reliability and Security ratings
- Code Duplication
- Test Coverage (from `coverage.xml`)

The last `main` analysis (10 July 2026) reported 0 bugs, 3 vulnerabilities and 9 code smells.

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

- Fix the stopped scans (see above)
- Run CI on pushes to `main` so `main` is analyzed after each merge
- Make the scan blocking (remove `continue-on-error`) and enforce the Sonar Quality Gate before merging

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
