# GitHub Branch Protection Rules

## Purpose

The `main` branch represents the production-ready version of the project.

These rules help ensure that only reviewed and validated code is merged into `main`, reducing the risk of introducing broken or insecure changes.

---

## Protected Branch

```
main
```

---

## Rules Configured

### 1. Require a Pull Request Before Merging

**Enabled:** ✅

All changes must be merged through a Pull Request.

Direct development should happen on feature or develop branches.

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

Current required checks:

- Laravel Pint
- Larastan
- PHPUnit
- Composer Audit
- Any future required GitHub Action checks

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

This remains disabled while developing the project independently.

It can be enabled later to completely prevent direct pushes to `main`.

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
 ├── Laravel Pint
 ├── Larastan
 ├── PHPUnit
 ├── Composer Audit
 └── SonarCloud (optional)
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
- Restrict direct pushes to `main`
- Require CODEOWNERS reviews
- Require signed commits