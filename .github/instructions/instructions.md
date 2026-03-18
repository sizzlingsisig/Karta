---
description: Laravel 12 project guidelines for PHP, Blade, Livewire, Tailwind, migrations, testing, Laravel Boost tools, and package-specific workflows including Scout, Reverb, and Spatie packages.
applyTo:
  - "**/*.php"
  - "**/*.blade.php"
  - "resources/**"
  - "routes/**"
  - "database/**"
  - "tests/**"
  - "docs/**"
---

# Karta Project Guidelines

Use these instructions when generating code, reviewing changes, proposing architecture decisions, or writing documentation in this repository.

# Quick Rules of Thumb
REM (Root EM): Use for font sizes, padding, margins, and layouts to ensure consistency across the page.
EM (Element EM): Use for properties that should scale with a component's font size (e.g., icons, padding inside buttons).
PX: Use only for borders, shadows, or when you need absolute precision that should not change. 

## Project Context

- Framework: Laravel 12
- PHP: 8.4 (target modern PHP 8.x syntax and typing)
- Backend stack: Sanctum, Scout, Reverb, Livewire 4, Spatie Permission, Spatie Medialibrary, Spatie Activitylog, Meilisearch, Intervention Image
- Frontend stack: Vite, Tailwind CSS v4, DaisyUI, Axios, Laravel Echo, SortableJS
- Test framework: PHPUnit 11

## Package Baseline

- Core Laravel ecosystem: laravel/framework, laravel/sanctum, laravel/scout, laravel/reverb, livewire/livewire, laravel/pint, laravel/sail, laravel/boost, laravel/mcp, laravel/pail
- Domain and utility packages: spatie/laravel-permission, spatie/laravel-medialibrary, spatie/laravel-activitylog, intervention/image, meilisearch/meilisearch-php, robsontenorio/mary
- Frontend dependencies: tailwindcss, daisyui, laravel-echo, axios, sortablejs, vite

Optional future additions should be feature-driven. Good candidates include queue monitoring, analytics, advanced notification channels, and advanced mail tooling.

## Repository Layout

- App logic: `app/`
- HTTP routes: `routes/`
- Views and UI templates: `resources/views/`
- Frontend entry and scripts: `resources/js/`
- Styles: `resources/css/`
- Database migrations/factories/seeders: `database/`
- Automated tests: `tests/`
- Product and architecture docs: `docs/`

## Core Engineering Rules

1. Follow Laravel conventions and existing project structure before introducing new patterns.
2. Keep changes minimal, focused, and scoped to the requested task.
3. Prefer extending existing services/components over introducing parallel abstractions.
4. Do not change dependencies without explicit user approval.
5. Do not create new top-level folders unless explicitly requested.
6. Do not create documentation files unless explicitly requested.
7. Reuse existing components before introducing new ones.

## Skill Activation Rules

1. Activate the Scout workflow whenever implementing search or index behavior.
2. Activate the Livewire workflow whenever tasks involve Livewire components or wire directives.
3. Activate the Echo workflow for broadcasting, channels, websocket events, or realtime features.
4. Activate the Tailwind workflow whenever tasks involve Tailwind classes, layouts, or component styling.
5. Activate the Laravel Permission workflow whenever implementing roles, permissions, policies, or related middleware.

## PHP and Laravel Standards

1. Always use explicit parameter and return types.
2. Use constructor property promotion when appropriate.
3. Always use braces for conditionals and loops.
4. Prefer Eloquent models and relationships over raw SQL.
5. Prevent N+1 queries using eager loading.
6. Use Form Requests for controller validation.
7. Use policies/gates for authorization logic.
8. Never call `env()` outside config files; use `config()` everywhere else.
9. Keep business logic out of Blade templates and route files.
10. Prefer PHPDoc for complex signatures or array shapes.
11. Avoid inline comments unless logic is genuinely non-obvious.

## Livewire, Blade, and Frontend Rules

1. Prefer Livewire components for reactive features in server-driven UI.
2. Keep Blade templates presentational; move behavior to Livewire classes or service classes.
3. Use Tailwind utility classes and existing design conventions in the project.
4. Keep JavaScript modular and scoped under `resources/js/`.
5. For realtime features, use Reverb + Echo with proper channel authorization.
6. If UI changes do not appear, verify frontend build workflow and ask to run npm run dev or npm run build.

## Laravel 12 Architecture Rules

1. Configure middleware in bootstrap/app.php, not app/Http/Kernel.php.
2. Use bootstrap/providers.php for application service providers.
3. Use routes/console.php or bootstrap/app.php for console configuration.
4. Assume command classes in app/Console/Commands are auto-discovered.

## Database and Migrations

1. Include all original attributes when altering existing columns to avoid accidental schema loss.
2. Add corresponding factory and, when useful, seeder changes for new models.
3. Use foreign keys and indexes intentionally for query performance and integrity.
4. Keep migration files atomic and reversible.
5. Mention deployment steps whenever migrations are introduced or modified.

## Testing and Quality Gates

1. Add or update PHPUnit tests for non-trivial behavior changes.
2. Cover happy path, failure path, and authorization/validation edge cases.
3. Use factories for test setup instead of hard-coded fixtures where possible.
4. Run targeted tests first, then broader tests if changes impact shared behavior.
5. Run formatting on PHP changes using:
   - `vendor/bin/pint --dirty --format agent`
6. Use PHPUnit style tests; convert Pest style only if encountered.
7. Do not remove tests without explicit approval.

## Build and Runtime Commands

- Local backend/frontend dev: `composer run dev`
- Frontend dev only: `npm run dev`
- Frontend production build: `npm run build`
- Tests: `php artisan test --compact`
- Package publish discovery: `php artisan vendor:publish`

## Laravel Boost and Tooling Rules

1. Use Laravel Boost tooling whenever available for package-aware workflows.
2. Search Laravel docs using the version-aware search tool before implementing framework-level changes.
3. Use database-query for read-only inspection and schema tools before writing migrations.
4. Use artisan commands directly for route inspection, config inspection, and command discovery.
5. Use broad topic-based documentation searches before narrow implementation guesses.

## Documentation and API Contract Rules

1. Update relevant files in `docs/` when behavior, architecture, or product flows change.
2. Keep `docs/openapi.yaml` in sync with API route and payload changes.
3. Document new environment variables, queue requirements, or operational steps.

## Pull Request Expectations

When preparing a PR or PR-style summary, include:

1. What changed and why.
2. Any migration or deployment impact.
3. Tests added or updated.
4. Commands run for verification.
5. Risks, assumptions, and follow-up tasks.

## Package and Dependency Policy

1. Keep dependency additions explicit, justified, and approved before installation.
2. Keep package versions updated for security and compatibility.
3. Prefer removing unused dependencies to keep the app lean.

## Agent Decision Policy

1. If a request is ambiguous and could cause architectural drift, ask for clarification.
2. If a requested change is high-risk (auth, billing, destructive schema updates), propose a safer alternative first.
3. If package installation fails, diagnose root cause and provide exact remediation commands.
4. If changes are not visible in UI, remind to run frontend build/dev commands.
5. Use concise responses focused on high-value details.

## Definition of Done

A task is complete when:

1. Code follows project conventions and compiles.
2. Relevant tests pass.
3. PHP formatting has been applied.
4. Documentation and API contract are updated when applicable.
5. Required migration/deployment commands are clearly communicated.
