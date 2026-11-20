# How a Laravel request runs (compile time vs runtime)

Revision notes. Example request: `GET /daily-routine` in BrainHub
(server = NGINX + PHP-FPM, see `Dockerfile`).

## The flow

```
 Browser ── GET /daily-routine ──► NGINX ──► PHP-FPM worker (empty memory)
                                                │
════════════════════════════════════════════════╪══════════════════════════════
 FILE 1: public/index.php                       ▼
────────────────────────────────────────────────────────────────────────────────
 [COMPILE] parse whole index.php → opcodes
           ✔ syntax checked (a syntax error here → Parse error, NOTHING runs,
             every page breaks because every request starts here)
           (OPcache hit? → skip, reuse saved opcodes)
────────────────────────────────────────────────────────────────────────────────
 [RUNTIME] line 6: define('LARAVEL_START', microtime(true))
                   ↑ microtime() runs NOW → clock read → constant created
           line 14: require vendor/autoload.php ─────────────┐
                                                             ▼
        ┌─ FILE 2: autoload.php ─────────────────────────────────────────┐
        │ [COMPILE] parse → [RUNTIME] register autoloader                │
        └────────────────────────────────────────────────────────────────┘
           line 18: require bootstrap/app.php ───────────────┐
                                                             ▼
        ┌─ FILE 3: bootstrap/app.php ────────────────────────────────────┐
        │ [COMPILE] parse → [RUNTIME] configure routes/middleware,       │
        │           create the app ($app)                                │
        └────────────────────────────────────────────────────────────────┘
           line 20: $app->handleRequest(Request::capture())
                    │
                    ▼  each class below is loaded by the autoloader on FIRST use:
        ┌─ Middleware → Router ──────────────────────────────────────────┐
        │  routes/web.php      [COMPILE] → [RUNTIME] register routes     │
        │  RoutineWeekController.php                                     │
        │     [COMPILE] class definition, methods, class `const` values  │
        │     [RUNTIME] show($date) runs: $monday = ..., fn(...) => ...  │
        │  RoutineWeek.php (model)                                       │
        │     [COMPILE] class + methods                                  │
        │     [RUNTIME] forDate() → SQL query → MySQL → object           │
        │  Model.php (Laravel vendor)                                    │
        │     [COMPILE] const CREATED_AT = 'created_at' ← fixed here     │
        │  Blade view                                                    │
        │     [COMPILE] .blade.php → cached plain PHP (storage/framework │
        │               /views), then PHP compiles that to opcodes       │
        │     [RUNTIME] HTML built using your variables                  │
        └────────────────────────────────────────────────────────────────┘
                    │
                    ▼
 HTML response ──► NGINX ──► Browser
                    │
 Request ends → ALL runtime memory wiped: LARAVEL_START, statics, $vars, objects
             → OPcache keeps COMPILED opcodes (code only) for the next request
════════════════════════════════════════════════════════════════════════════════
```

## Key rules

1. **Compile time** = PHP reads a file and turns it into opcodes. Syntax errors
   and `const` values are handled here. Nothing runs.
2. **Runtime** = the opcodes execute: functions run, DB is queried, `define()` runs.
3. **Compile and run alternate, file by file.** A file is compiled only when it is
   `require`d or autoloaded. Files a request never uses are never compiled.
4. **Inside one file, the whole file is compiled first**, then its lines run.
   All its functions are compiled, but only the called ones run.
5. **Share-nothing:** every request starts with empty memory and everything is
   wiped at the end. Permanent data lives in MySQL, the session or the cache.
6. **OPcache** caches compiled code only, never variable or constant values.

## define() vs const

```
const   = value written into the CODE while compiling → fixed values only
define  = value created while the CODE RUNS           → can use microtime(), if, variables
```

| | `const` | `define()` |
|---|---|---|
| Value decided at | compile time | runtime |
| Function call as value | ❌ | ✅ `define('LARAVEL_START', microtime(true))` |
| Inside a class | ✅ `const DONE = 'done';` | ❌ |
| Inside a function / `if` | ❌ | ✅ |
| Where it can be **used** | everywhere | everywhere (after it ran) |
| Lifetime | one request | one request |

**Remember:** fixed value in a class → `const` (about 95% of cases).
Value needs a function call → `define()`. Everything else → `$variable`.

## Interview one-liner

> Every request enters through `public/index.php`. Laravel boots the service
> container and providers, passes the request through middleware, and the router
> picks a controller that uses models and returns a view. Each file is compiled to
> opcodes when first loaded, then executed. PHP is share-nothing, so all variables,
> constants and statics live for one request only; persistent state goes in the
> DB, session or cache. OPcache only caches the compiled code.
