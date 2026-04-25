# AGENTS.md

## Use These Commands
- Setup: `composer setup`.
- Local dev: `composer dev`.
  Starts `php artisan serve` and `php artisan queue:listen` concurrently.
- Run tests: `composer test`.
- PHP formatting: `composer lint` to fix, `composer lint:check` to verify.

## Testing
- Test runner is Pest. Focus a test: `./vendor/bin/pest --filter <name>`.
- `composer test` clears config, runs lint, then runs tests.
- Test DB is in-memory SQLite from `phpunit.xml`.

## API Integration
- Backend runs on port 8000. Handles auth (Laravel Fortify), permissions (Spatie), and Inertia SSR.
- Inertia SSR entry point is `bootstrap/ssr.php`. Run `php artisan inertia:ssr` to build.
- Frontend URL is configured via `FRONTEND_URL` env var for CORS.
- Asset path is served from `public/`. Vite build outputs go in `public/build/`.
- Blade templates for SSR are in `resources/views/app.blade.php`.