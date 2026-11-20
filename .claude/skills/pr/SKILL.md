---
name: pr
description: BrainHub PR workflow. Look at the changes, suggest a branch, optionally run Pint + PHPStan + PHPUnit safely (never against the real DB), preview the commit message and PR description, and only after approval commit, push and create the PR. Use when the user asks to check, prepare or open a PR.
---

# BrainHub PR workflow

Golden rule: ask before every change (branch, fix, commit, push, PR). Each ❓ below is a question to the user — wait for the answer before going on.

## 1. Look at the changes
- Run `git status`, `git branch --show-current` and `git diff --stat` (include untracked files).
- Give a short summary: which files changed and what the change is about (e.g. "3 Blade files + 1 SVG = logo change").
- If the changes have mixed purposes (e.g. logo + docker config), say so and suggest splitting them into separate branches/PRs, with a branch name and file list for each.
- Never include personal files: `.env`, `docs/plan/bin.md`, `command.md`, `.claude/settings.local.json`.

## 2. Branch ❓
- If on `main` or `develop`: suggest a new branch name based on the change (`feature/…`, `fix/…`, `chore/…`, `docs/…`, kebab-case, short).
- If on a topic branch: say whether its name fits the changes, then ask:
  - a) Use the current branch `<name>`
  - b) Create a new branch `<suggested-name>`
- Create the branch only after the user picks it (`git switch -c <name>`; uncommitted changes come along).

## 3. Run the three checks? ❓
Ask: "Run Pint + PHPStan + tests before commit? (recommended)". If yes, run in this order, stop at the first failure:
1. Style: `docker compose exec app vendor/bin/pint --test`
2. Static analysis: `docker compose exec app vendor/bin/phpstan analyse`
3. Tests: `docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= app php artisan test`

NEVER run `php artisan test` in the container without these -e overrides: it wipes the real local MySQL database.

- If the containers are not running, ask before `docker compose up -d`.
- If Pint fails: ask before running `vendor/bin/pint` to fix the files.
- If PHPStan or tests fail: explain the error in simple words and ask before changing code.

Show a summary table:

| Check | Result |
|---|---|
| Pint | PASS / FAIL / skipped |
| PHPStan | PASS / FAIL (n errors) / skipped |
| Tests | PASS / FAIL (passed / failed counts) / skipped |

## 4. Preview ❓
Show everything before doing anything:
- **Files to stage** (exact list).
- **Commit message** — conventional: `feat(scope): …`, `fix(…)`, `docs(…)`, `chore(…)`; short subject, optional body explaining why.
- **PR title** — same style as the commit subject.
- **PR description** — fill in `.github/pull_request_template.md` (What / Why / Changes / How to test / Checklist). Tick a checklist box only if it was really checked in step 3 (or is truly n/a, say so). Base branch: `develop`.

Ask: "Approve? (yes / edit / cancel)". On edit, change and show again.

## 5. Create (only after "yes")
1. `git add <listed files>` (never `git add .`), then `git commit`.
2. `git push -u origin <branch>`.
3. If `gh` is installed and logged in (`gh auth status`): `gh pr create --base develop --title "<title>" --body "<description>"` and show the PR link.
4. If not: show the description ready to paste and the link `https://github.com/<owner>/<repo>/compare/develop...<branch>?expand=1` (from `git remote get-url origin`).

## 6. Clean up after the PR ❓
Only after the PR was created successfully (link shown). Ask: "Switch to develop, update it and delete local branch `<branch>`? (yes / no)"
1. If there are other uncommitted changes, list them first and say they will move with the switch to develop.
2. `git switch develop`
3. `git pull --ff-only origin develop` (get the latest develop; stop and explain if it can't fast-forward)
4. `git branch -d <branch>` (safe delete; it only works because the branch was pushed. Never use `-D`)
5. Show `git status` and `git log --oneline -3` so the user sees they're on the latest develop.

Note: the branch is still on GitHub. If the PR needs changes later, run `git switch <branch>` to get it back from origin.
