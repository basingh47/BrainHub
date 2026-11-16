# My Commands

Personal cheat sheet. Not committed (it's in `.gitignore`).

## Docker
| Command | What it does |
|---|---|
| `docker compose up -d` | Start all containers in the background |
| `docker compose up -d --build` | Rebuild the image, then start (only needed after a Dockerfile change) |
| `docker compose down` | Stop and remove the containers and the network (data volumes stay) |
| `docker compose ps` | Show running containers and their status |
| `docker compose logs -f app` | Follow the app container's logs live |
| `docker compose exec app bash` | Open a shell inside the app container |
| `docker compose exec app php artisan tinker` | Open Tinker (a PHP shell with Laravel loaded) inside Docker |
| `docker compose exec --user $(id -u):$(id -g) app php artisan ...` | Run artisan in Docker as your user, so created files aren't owned by www-data |
| `docker compose exec mysql mysql -ubrainhub -psecret brainhub -e "SELECT * FROM daily_tasks;"` | Run SQL directly in the MySQL container |

## Artisan: create files (run on your PC)
| Command | What it does |
|---|---|
| `php artisan make:controller DailyTaskController --resource` | Create a controller with the 7 resource methods |
| `php artisan make:model DailyTask -m` | Create a model plus its migration |
| `php artisan make:seeder DailyTaskSeeder` | Create a seeder class |
| `php artisan make:factory DailyTaskFactory --model=DailyTask` | Create a factory linked to a model |

## Artisan: database (run in Docker)
| Command | What it does |
|---|---|
| `docker compose exec app php artisan migrate` | Run new migrations (create/change tables) |
| `docker compose exec app php artisan migrate:status` | List which migrations have run |
| `docker compose exec app php artisan db:seed --class=DailyTaskSeeder` | Run one seeder to insert data |
| `docker compose exec app php artisan migrate:fresh --seed` | Drop ALL tables, re-migrate, run DatabaseSeeder (deletes everything) |

## Artisan: other
| Command | What it does |
|---|---|
| `php artisan route:list` | Show all routes (URL, name, controller method) |
| `php artisan optimize:clear` | Clear all Laravel caches (config, route, view, event) |
| `php artisan test` | Run the PHPUnit tests |

## Code quality
| Command | What it does |
|---|---|
| `vendor/bin/pint` | Auto-fix code style (Laravel standard) |
| `vendor/bin/pint --test` | Check style only, change nothing (what CI runs) |
| `vendor/bin/phpstan analyse` | Static analysis with Larastan (finds bugs without running the code) |

## Git
| Command | What it does |
|---|---|
| `git status` | Show changed, staged and untracked files |
| `git switch -c feature/name` | Create a new branch and switch to it |
| `git add <file>` | Stage a file for the next commit |
| `git commit -m "message"` | Save staged changes as a commit |
| `git push -u origin <branch>` | Push the branch to GitHub and track it |
| `git fetch --prune` | Get the latest from GitHub and forget deleted remote branches |
| `git branch -d <branch>` | Delete a local branch (only if it's merged) |
| `git merge origin/develop` | Bring the latest develop into your current branch |
