# GitHub Branch Protection Rules

## Purpose

The `main` branch represents the production-ready version of the project.

These rules help ensure that only reviewed and validated code is merged into `main`, reducing the risk of introducing broken or insecure changes.

---

## Protected Branch

```
main
```

Rules are set as a GitHub **ruleset** (Settings → Rules). A rejected push shows `GH013: Repository rule violations found for refs/heads/main`.

---

## Rules Configured

### 1. Require a Pull Request Before Merging

**Enabled:** ✅

All changes must be merged through a Pull Request. A direct `git push` to `main` is rejected with:

```
- Changes must be made through a pull request.
- Required status check "ci" is expected.
```

Work on a branch (`feature/*`, `fix/*`, `docs/*`, …) and open a PR.

Example:

```
feature/auth
        │
        ▼
Pull Request
        │
        ▼
main
```

---

### 2. Require Status Checks to Pass

**Enabled:** ✅

A Pull Request cannot be merged until all required CI checks complete successfully.

Required check: **`ci`** — the single job in `.github/workflows/ci.yml`.

That job fails if any blocking step fails: asset build, Laravel Pint, Larastan, PHPUnit, or Composer Audit. SonarCloud runs inside it but is non-blocking. See [GitHub Actions](../ci-cd/github-actions.md).

If any check fails:

- Merge is blocked.
- The issue must be fixed.
- CI must pass before merging.

---

### 3. Required Approvals

**Current Setting:** `0`

Since this is currently a solo-developed project, reviewer approval is not required.

For team development, this can be changed to:

- 1 approval
- 2 approvals

---

### 4. Require Conversation Resolution

**Enabled:** ✅

All Pull Request review conversations must be resolved before the Pull Request can be merged.

---

### 5. Block Force Pushes

**Enabled:** ✅

Force pushes to the `main` branch are not allowed.

This prevents accidental history rewrites and protects production history.

---

### 6. Restrict Updates

**Current Setting:** Disabled

Not needed: rule 1 already rejects direct pushes to `main`. This setting would additionally limit *who* can update the branch.

---

### 7. Branch Must Be Up-to-Date Before Merge

**Current Setting:** Disabled

This rule is typically enabled in larger teams to ensure feature branches include the latest changes from `main` before merging.

For this project it is currently unnecessary.

---

## Development Workflow

```
feature/*
      │
      ▼
Push to GitHub
      │
      ▼
Create Pull Request
      │
      ▼
GitHub Actions
 ├── Build assets (Vite)
 ├── Laravel Pint
 ├── Larastan
 ├── PHPUnit
 ├── Composer Audit
 └── SonarCloud (non-blocking)
      │
      ▼
All checks pass?
      │
 ┌────┴────┐
 │         │
No        Yes
 │         │
Fix      Merge
 │         │
 └─────────▼
        main
          │
          ▼
Automatic Deployment (Render)
```

---

## Benefits

- Prevents broken code from reaching `main`
- Enforces automated quality checks
- Protects Git history
- Encourages code review through Pull Requests
- Keeps the production branch stable
- Matches common GitHub workflows used by professional software teams

---

## Future Improvements

When the project grows into a team project, consider enabling:

- Require 1 reviewer approval
- Require branches to be up to date before merging
- Require CODEOWNERS reviews
- Require signed commits

---

*Last updated: 4 November 2026*
