# Repronig Codebase Guidelines

## Summary

This repository is a Laravel 13 API application focused on rights management, licensing, membership, and usage declarations for educational institutions, associations, and contributors.

## High-level architecture

- Laravel 13, PHP 8.3.
- API-first architecture using `routes/api.php` and `App\Http\Controllers\Api\V1`.
- Auth via `laravel/sanctum`.
- Role and permission management via `spatie/laravel-permission`.
- Business logic lives in `app/Actions/*`.
- Models live in `app/Models/*`.
- A domain folder exists at `app/Domain`, but it is currently empty.
- Controllers are intentionally thin and delegate to action classes.

## Key structural patterns

### Controllers

- Controllers are organized by API area: `Auth`, `Member`, `Institution`, `Association`, `Admin`, `Super`, `Webhooks`, and `Public`.
- Controllers extend `App\Http\Controllers\Api\V1\BaseApiController`.
- `BaseApiController` uses `App\Support\ApiResponse` to provide `success()`, `created()`, `paginated()`, and `error()` JSON helpers.
- Most controllers still manually return `response()->json(...)` instead of consistently using the base trait helpers.
- Request validation is handled via `App\Http\Requests\Api\V1` classes.
- Authorization is used in some controller actions (for example, updating an institution profile).

### Actions

- `app/Actions` contains the application service layer.
- Actions are grouped by domain area (`Access`, `Institutions`, `Licensing`, `MemberOnboarding`, `Payments`, `Works`, `Audit`, etc.). Identity/session concerns use **`Access`** (avoid a second top-level `Auth` folder; see `docs/authorization-strategy.md` for route vs policy rules).
- Actions encapsulate operations such as login, registration, work submission, licence application, and payment webhook handling.
- Database mutations are often wrapped in transactions inside actions.
- `LogAuditAction` is the centralized audit logger.

### Models

- There are about 20 Eloquent models.
- Models use `HasFactory` and define `$fillable` and `$casts`.
- `User` uses PHP 8 attribute metadata (`#[Fillable]`, `#[Hidden]`) and a `casts()` method returning cast rules.
- Relationships are defined across the domain, e.g. `Member` belongs to `User` and `Association`, `Association` has many `Member`s.

## Routes and authorization patterns

- API routes are grouped under `v1`.
- Public routes include auth registration, login, and association lookup.
- Protected route groups are based on roles:
  - `member`
  - `association_officer`
  - `institution_user`
  - `admin|super_admin`
  - `super_admin`
- Webhook route is exposed without role middleware, under `v1/payments/webhook`.
- Role-based route guarding is implemented via middleware in `routes/api.php`.

## Packages and tooling

- `laravel/framework` ^13
- `laravel/sanctum` ^4.0
- `spatie/laravel-permission` ^7.2
- `laravel/boost` ^2.4
- `laravel/pail` ^1.2.5
- `laravel/pint` ^1.27
- `pestphp/pest` ^4.4
- `pestphp/pest-plugin-laravel` ^4.1

## Observations and improvement notes

### Strengths

- Action-based service layer is a good separation of concerns.
- Role-based route segmentation is clear and aligned with business domains.
- The API response helper trait is a strong convention for standardized JSON.
- Audit logging is centralized via `LogAuditAction`.
- Modern PHP attribute usage in `User` is current and clean.

### Areas for improvement

- `app/Domain` is empty, so the intended domain layer is unused.
- Controllers should keep returning via `BaseApiController` / `ApiResponse` helpers end-to-end (typed `JsonResponse` return types are fine; avoid ad hoc envelope shapes).
- Many model relationship methods lack explicit return type declarations.
- Expand feature tests for newly added domains and regression-prone flows beyond the current matrix.
- `app/Actions` is a good service layer, but consistency around return types and error handling can still improve.

### Recommended follow-up actions

- Add tests covering API routes, action classes, and model relationships.
- Consolidate response handling using `ApiResponse` consistently.
- Keep route definitions grouped by resource and middleware; watch for accidental duplicate URI registrations when adding resources.
- Either populate `app/Domain` with domain services/value objects or remove it if unused.
- Add return types to model relationship methods and action methods where possible.
- Audit the use of authorization in controllers; some actions may require additional policy checks.

## Authorization (single source)

See `docs/authorization-strategy.md` for how route middleware, Spatie roles/permissions, policies, and `HandlesAdminOverride` interact.

See `docs/api-json-envelope.md` for JSON response consistency (`ApiResponse` vs streaming exports).

## Practical points for future development

- Keep controllers thin: validation, authorization, then action invocation.
- Keep actions small and focused; transaction boundaries belong in actions.
- Use resources for response shaping when returning models.
- Prefer explicit return types on actions, controllers, and model relation methods.
- Keep route definitions grouped by resource and middleware to avoid duplication.
- Expand test coverage before adding new behavior in critical domains like auth, licensing, and payments.

## Current codebase metrics

- `app/Actions`: ~134 PHP classes (grouped by domain folders under `app/Actions`)
- `app/Http/Controllers/Api/V1`: 35 controllers
- `app/Models`: 20 models
- `routes/api.php`: single main API route definition file
- `tests`: ~39 PHP test files (feature + unit); expand as behaviour grows

## Notes on the codebase purpose

The app appears to manage:
- association memberships and member onboarding
- institution registration and profile management
- licence applications and payments
- work registration and contributor management
- usage declarations for institutions
- audit logging and admin/super-admin configuration
