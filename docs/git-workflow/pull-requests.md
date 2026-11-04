# 🔀 Pull Request Workflow

Every change reaches `main` through a pull request. `main` is what runs live on Render, so it must always be working.

---

## 1. Start a branch

Always branch from the latest `main`:

```bash
git checkout main
git pull
git checkout -b feature/task-crud
```

Branching from an old or other branch brings in commits that are already on `main` (duplicates and conflicts).

### Branch names

`<type>/<short-description>` — lowercase, words joined with `-`.

| Type | Use for | Example |
|---|---|---|
| `feature/` | New functionality | `feature/task-crud` |
| `fix/` | Bug fix | `fix/login-redirect` |
| `chore/` | Dependencies, config, Docker, tooling | `chore/update-laravel` |
| `ci/` | GitHub Actions changes | `ci/run-on-main-push` |
| `docs/` | Documentation only | `docs/pr-workflow` |

---

## 2. Commit

One commit = one logical change. Don't mix code, CI and docs in one commit unless they belong together.

### Commit messages

```
Add task create and edit pages

Optional body: why the change was made, anything a reviewer
should know. Wrap at ~72 characters.
```

- Title: short (≤ 72 characters), imperative ("Add", "Fix", "Update", "Remove"), no trailing period.
- Body: optional, separated by a blank line.

| ✅ Good | ❌ Avoid |
|---|---|
| `Fix password reset link using http` | `fixed stuff` |
| `Update Laravel to fix composer audit` | `Update composer.lock; add docs; refactor Dockerfile.` |
| `Add profile avatar upload` | A whole paragraph on the title line |

### Check before pushing

Run the same checks CI runs:

```bash
vendor/bin/pint            # fix code style
vendor/bin/phpstan analyse # static analysis
php artisan test           # tests
composer audit             # dependency security
```

---

## 3. Open the PR

```bash
git push -u origin feature/task-crud
```

Open the link GitHub prints, or go to **Pull requests → New pull request**, base `main`.

- **Title:** same style as a commit title.
- **Description:** the template (`.github/pull_request_template.md`) is filled in automatically — complete each section.
- Keep PRs small and focused. One feature or fix per PR is easier to review and revert.

---

## 4. CI and review

- GitHub Actions runs the `ci` check automatically (see [GitHub Actions](../ci-cd/github-actions.md)).
- If it fails: open the run, read the **Explain … failure** step, fix locally, commit, push again. The PR updates by itself.
- Resolve all review conversations before merging.

---

## 5. Merge

Use **Squash and merge**. All commits in the PR become one clean commit on `main`, so fix-up commits ("fix typo", "fix CI") don't clutter history.

> Repository setting: GitHub → Settings → General → Pull Requests → allow only **Squash merging**.

Merging to `main` triggers a Render deploy. Check the site after it finishes.

---

## 6. Clean up

```bash
git checkout main
git pull
git branch -d feature/task-crud
```

Delete the remote branch with the **Delete branch** button on the merged PR (or enable **Automatically delete head branches** in repository settings).

---

## Common problems

| Problem | Fix |
|---|---|
| `GH013: Repository rule violations` on push | You pushed to `main`. Move the commit to a branch: `git branch my-branch && git reset --hard origin/main && git checkout my-branch`, then push the branch. See [branch protection](branch-protection.md). |
| PR shows conflicts | `git checkout my-branch && git merge origin/main`, fix conflicts, commit, push. |
| PR contains commits that are already on `main` | The branch was started from an old branch. Create a new branch from `main` and `git cherry-pick` only your commits. |
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

*Last updated: 4 November 2026*
