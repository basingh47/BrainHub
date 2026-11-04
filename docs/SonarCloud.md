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

SonarCloud is executed as part of the GitHub Actions CI pipeline.

Pipeline flow:

```
Push / Pull Request
        │
        ▼
GitHub Actions
        │
        ├── Laravel Pint
        ├── Larastan
        ├── PHPUnit
        ├── Composer Audit
        ├── Coverage Report
        └── SonarCloud Scan
```

Current workflow step:

```yaml
- name: SonarCloud scan
  uses: SonarSource/sonarqube-scan-action@v8
```

Updated 2026-07-29 from `@v4` (flagged by GitHub as unsupported and containing a security vulnerability) to `@v8`, the latest major version at the time.

---

## Current Project Status

Repository visibility:

- Private Repository

SonarCloud plan:

- Free Plan

Current limitation:

The free plan analyzes **only the default branch (`main`)** for private repositories.

Current branch status:

| Branch | Analysis |
|---------|----------|
| `main` | ✅ Analyzed |
| `develop` | ❌ Not analyzed (Free plan limitation) |

This limitation comes from the SonarCloud subscription plan and is **not** caused by the GitHub Actions workflow.

---

## Reports Available

The current SonarCloud dashboard provides reports for the `main` branch including:

- Overall code quality
- Bugs
- Vulnerabilities
- Security Hotspots
- Code Smells
- Maintainability Rating
- Reliability Rating
- Security Rating
- Code Duplication
- Test Coverage (when coverage reports are supplied)

These reports are updated whenever the `main` branch is analyzed.

---

## Current Workflow

```
feature/*
      │
      ▼
develop
      │
      ▼
GitHub Actions
      │
      ▼
Pull Request
      │
      ▼
main
      │
      ▼
SonarCloud Analysis
      │
      ▼
Render Deployment
```

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

When the project grows or moves to a paid SonarCloud plan:

- Analyze `develop`
- Analyze feature branches
- Analyze every Pull Request
- Enforce Sonar Quality Gates before merging
- Enable branch-specific quality reports

---

## Notes

Current setup is intended for learning and personal development.

GitHub Actions already provides:

- Laravel Pint
- Larastan
- PHPUnit
- Composer Audit

SonarCloud acts as an additional quality analysis tool and currently reports only on the `main` branch due to the limitations of the free plan.