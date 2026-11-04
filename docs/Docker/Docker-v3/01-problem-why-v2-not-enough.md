# Problem: why Docker-v2 is not enough

## v2 ran the dev server in production

v2's last line:

```dockerfile
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
```

`artisan serve` starts **PHP's built-in web server**. The PHP manual says it is
*"not intended to be a full-featured web server. It should not be used on a public network."*

---

## What goes wrong

| Problem | Effect on BrainHub |
|---|---|
| **One request at a time** | A slow page (e.g. sending a reset email) blocks every other visitor |
| **PHP serves static files** | Every CSS/JS/image request occupies the only PHP process |
| **No gzip / cache headers** | Browsers re-download assets; pages load slower |
| **No supervisor** | If the process dies, the whole container dies |
| **Migrations by hand** | v2 needed `php artisan migrate --force` in the Render shell after each deploy |
| **No config/route/view cache** | Laravel re-reads config and recompiles on every request |
| **Runs as root** | A bug in the app would have root inside the container |

---

## Why not two containers (NGINX + PHP-FPM separately)?

That is the classic setup, but **Render free runs one container per web service**
and ignores `docker-compose.yml`. Two Render services would need a paid private service.

So v3 runs both processes **inside one container** with a process supervisor (S6).
On a VPS later, the same idea can be split into two containers.

---

*Next: [02-solution-dockerfile.md](./02-solution-dockerfile.md)*
