# 📚 BrainHub Docs

| Folder / file | What's inside |
|---|---|
| [architecture.md](architecture.md) | Stack, how code goes from PR to live, which file each tool uses, database |
| [roadmap.md](roadmap.md) | Version 1 plan and progress |
| **plan/** | |
| [README.md](plan/README.md) | v1 product plan: three pages, build order, shared decisions |
| [01-daily-tasks.md](plan/01-daily-tasks.md) | Daily Tasks page: UI, data model, routes, rules, checklist |
| [02-daily-routine.md](plan/02-daily-routine.md) | Daily Routine page |
| [03-dashboard.md](plan/03-dashboard.md) | Dashboard page |
| **git-workflow/** | |
| [pull-requests.md](git-workflow/pull-requests.md) | Branch naming, commit messages, opening and merging a PR |
| [branch-protection.md](git-workflow/branch-protection.md) | Rules on `main` (PR required, `ci` check must pass) |
| **ci-cd/** | |
| [github-actions.md](ci-cd/github-actions.md) | CI steps, triggers, fixing common failures |
| [sonarcloud.md](ci-cd/sonarcloud.md) | Code quality scanning setup and status |
| **deployment/** | |
| [render-and-local.md](deployment/render-and-local.md) | Render settings, what happens on deploy, local Docker Compose |

## Where to start

- **New to the project:** architecture → pull-requests → render-and-local
- **CI failed on your PR:** github-actions (Troubleshooting)
- **Push to `main` rejected:** branch-protection
- **Changing production settings:** render-and-local
