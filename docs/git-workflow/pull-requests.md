# 🔀 Pull Request Workflow

BrainHub uses two long-lived branches:

| Branch | Holds | Who writes to it |
|---|---|---|
| `develop` | the next release — finished, tested features | PRs from feature branches |
| `main` | **what runs live on Render** — every merge deploys | release PRs from `develop` (and rare hotfixes) |

Both are protected: no direct pushes, a PR is required, and the `ci` check must pass (see [branch protection](branch-protection.md)).

```
feature/xyz ──PR──► develop ──release PR──► main ──► Render deploy
     ▲                  │                     │
     └── branched from  │                     │
         latest develop └──◄── back-merge ◄───┘ (only after a hotfix to main)
```

---

## 1. Start a branch

Always branch from the latest `develop`:

```bash
git checkout develop
git pull
git checkout -b feature/routine-manage
```

Branching from an old or other branch brings in commits that are already on `develop` (duplicates and conflicts).

### Branch names

`<type>/<short-description>` — lowercase, words joined with `-`.

| Type | Use for | Example |
|---|---|---|
| `feature/` | New functionality | `feature/routine-manage` |
| `fix/` | Bug fix | `fix/login-redirect` |
| `chore/` | Dependencies, config, Docker, tooling | `chore/docker-env-single-source` |
| `ci/` | GitHub Actions changes | `ci/run-on-main-push` |
| `docs/` | Documentation only | `docs/refresh-project-docs` |

---

## 2. Commit

One commit = one logical change (for example: tables + models, then the page, then tests). Don't mix unrelated code, CI and docs in one commit.

### Commit messages (Conventional Commits)

```
feat(routine): create and edit weekly sheets

Optional body: why the change was made, anything a reviewer
should know. Wrap at ~72 characters.
```

- Title: `<type>(<scope>): <what>` — short (≤ 72 characters), imperative, no trailing period.
- Types: `feat`, `fix`, `test`, `docs`, `chore`, `ci`, `refactor`.
- Scope: the area, e.g. `routine`, `tasks`, `docker`.
- Body: optional, separated by a blank line.

| ✅ Good | ❌ Avoid |
|---|---|
| `fix(tasks): keep completed_at when editing a done task` | `fixed stuff` |
| `test(routine): feature tests for the weekly sheet` | `Update composer.lock; add docs; refactor Dockerfile.` |
| `docs: describe the develop → main release flow` | A whole paragraph on the title line |

### Check before pushing

Run the same checks CI runs:

```bash
vendor/bin/pint             # fix code style
vendor/bin/phpstan analyse  # static analysis
php artisan test            # tests (phpunit.xml forces SQLite in memory — never touches MySQL)
composer audit              # dependency security
```

---

## 3. Open the PR into `develop`

```bash
git push -u origin feature/routine-manage
```

Open the link GitHub prints, or go to **Pull requests → New pull request**: base **`develop`** ← compare your branch.

- **Title:** same style as a commit title.
- **Description:** what changed, how to test it, what is not included yet.
- Keep PRs small and focused. One feature or fix per PR is easier to review and revert.

---

## 4. CI and review

- GitHub Actions runs the `ci` check automatically (see [GitHub Actions](../ci-cd/github-actions.md)).
- SonarCloud needs **≥ 80 % test coverage on new code** — ship tests with the feature.
- If it fails: open the run, read the **Explain … failure** step, fix locally, commit, push again. The PR updates by itself.

---

## 5. Merge into `develop`

Use **Create a merge commit** (not squash). The feature's commits (tables, page, tests) stay visible in history, and a release PR to `main` contains the same commits — no duplicates.

---

## 6. Release: `develop` → `main`

When `develop` holds something worth shipping:

1. **Pull requests → New pull request**: base **`main`** ← compare **`develop`**.
2. Title: `release: <what users get>`. Description: included PRs, production notes (new migrations? env changes?), what to check after deploy.
3. CI runs again on the combined code. Merge with **Create a merge commit**.
4. Render deploys `main` automatically. Before merging, make sure the Aiven database is **Running** (the free plan powers off when idle). After the deploy, check the live site.

The release PR shows commits you already reviewed — that is expected. It doesn't add code; it **promotes** tested code to production and gives each deploy its own record.

### After a hotfix to `main`: back-merge

If something was merged straight into `main` (e.g. a hotfix), bring it back so `develop` doesn't fall behind: open a PR base **`develop`** ← compare **`main`** and merge it.

---

## 7. Clean up

```bash
git checkout develop
git pull
git fetch --prune                       # forget remote branches deleted on GitHub
git branch -d feature/routine-manage    # -d refuses if the branch is not merged
```

Delete the remote branch with the **Delete branch** button on the merged PR. Local and remote branches are separate copies — deleting one never deletes the other.

---

## Common problems

| Problem | Fix |
|---|---|
| `GH013: Repository rule violations` on push | You pushed to a protected branch. Move the commit to a branch: `git branch my-branch && git reset --hard origin/develop && git checkout my-branch`, then push the branch. See [branch protection](branch-protection.md). |
| PR shows conflicts | `git checkout my-branch && git merge origin/develop`, fix conflicts, commit, push. |
| Release PR shows conflicts | `main` has commits `develop` lacks (a hotfix). Back-merge `main` into `develop` first (via a small PR), then release. |
| PR contains commits that are already on `develop` | The branch was started from an old branch. Create a new branch from `develop` and `git cherry-pick` only your commits. |
| Local branch shows `[gone]` | Its remote was deleted after merge. `git checkout develop && git branch -d <branch>`. |
| Committed on the wrong branch, not pushed yet | `git reset --soft HEAD~1` undoes the commit and keeps the changes. |

---

## Author identity

Use one name and email for all commits in this repository:

```bash
git config user.name "Ankit Singh"
git config user.email "basingh47@gmail.com"
```

(Set per repository, without `--global`, if you use a different identity for work projects.)

---

*Last updated: 20 November 2026*
