<!--
  Sync Impact Report
  ==================
  Version change: N/A → 1.0.0 (initial ratification)
  Modified principles: None (initial creation)
  Added sections:
    - Core Principles (6 principles)
    - Security & Authentication
    - Code Style & Conventions
    - Governance
  Removed sections: None
  Templates requiring updates:
    - .specify/templates/plan-template.md ✅ no changes needed
    - .specify/templates/spec-template.md ✅ no changes needed
    - .specify/templates/tasks-template.md ✅ no changes needed
    - .specify/templates/checklist-template.md ✅ no changes needed
    - .specify/templates/agent-file-template.md ✅ no changes needed
  Follow-up TODOs: None
-->

# Currere Constitution

## Core Principles

### I. Laravel Conventions First

All development MUST use Laravel's built-in tools and follow idiomatic
Laravel patterns. Sanctum for API authentication, Fortify for web
authentication, Eloquent for data access, standard migrations, Form
Request classes for validation, and Eloquent API Resources for API
responses. Prefer `Model::query()` over `DB::` facades. Use named routes
and the `route()` helper for URL generation. Use queued jobs for
long-running operations. Never call `env()` outside config files.

**Rationale**: The Laravel ecosystem provides tested, documented
solutions for nearly every common need. Custom implementations add
maintenance burden without proportional benefit.

### II. Extend the Starter Kit

The frontend uses the official Laravel Svelte starter kit (Svelte 5,
Inertia 2, TypeScript, Tailwind 4, shadcn-svelte). All frontend work
MUST extend the starter kit's existing patterns and component library.
Replacing or fighting the starter kit's conventions is prohibited.
Wayfinder MUST be used for all backend route references in frontend
components.

**Rationale**: The starter kit provides a cohesive, maintained
foundation. Deviating from it creates drift that makes upgrades painful
and introduces inconsistency.

### III. API-First Data Layer

All run data originates from the Android app via a versioned REST API
(`/api/v1`). The web dashboard reads from the same database. The API
MUST be clean, use Eloquent API Resources, and be protected by Sanctum
API tokens. API endpoints MUST use invokable controllers for
single-action operations.

**Rationale**: The mobile app is the primary data source. A well-defined
API boundary keeps the web and mobile concerns decoupled and the data
layer consistent.

### IV. AI via Laravel AI SDK

All AI features MUST use the official `laravel/ai` package. AI
capabilities MUST be built as proper Agent classes with structured
output schemas. Because the AI SDK is in beta, the latest documentation
MUST be researched before implementing any AI feature.

**Rationale**: Using the official SDK ensures long-term support and
Laravel ecosystem integration. Structured output makes AI behavior
testable and predictable.

### V. Pragmatic Testing

Every change MUST be tested using Pest. Testing effort MUST focus on:
API endpoint correctness, AI agent structured output schemas, and domain
logic (pace calculations, aggregations, data transformations). Inertia
page rendering MUST NOT be over-tested. Use factories for model creation
in tests. Feature tests are the default; unit tests only when testing
isolated logic.

**Rationale**: Testing where bugs are most costly (API contracts, domain
logic, AI output) maximizes confidence per test written. Over-testing
rendering creates brittle tests with low signal.

### VI. Simplicity for Solo Use

This is a personal project for a single user (possibly a few users
later). All design decisions MUST favor simplicity over scalability.
No premature abstractions, no speculative features, no over-engineering
for hypothetical multi-tenancy or high traffic. YAGNI applies
rigorously. SQLite for development, PostgreSQL or MySQL for production.
Keep migrations simple and direct.

**Rationale**: Complexity is the primary threat to a solo project's
longevity. Every abstraction layer is maintenance debt that a single
developer must carry indefinitely.

## Security & Authentication

- Web login MUST use email/password via the starter kit (Fortify).
- API access MUST use Sanctum API tokens.
- API keys MUST be generateable from the web dashboard.
- API keys MUST be displayable as QR codes for easy mobile pairing.
- Authentication middleware MUST be configured in `bootstrap/app.php`
  per Laravel 12 conventions.

## Code Style & Conventions

- PHP code MUST pass Laravel Pint with default configuration.
- PHP files MUST use `declare(strict_types=1)`.
- All methods MUST have explicit return type declarations and parameter
  type hints.
- Invokable controllers MUST be used for single-action endpoints.
- PHPDoc blocks are preferred over inline comments.
- TypeScript strict mode MUST be enabled for all frontend code.
- Svelte components MUST follow the patterns established by the starter
  kit and shadcn-svelte.

## Governance

This constitution is the authoritative source for project-wide
decisions. All feature specifications, implementation plans, and task
lists MUST comply with these principles. When a principle conflicts with
a framework default, the principle takes precedence.

**Amendment procedure**: Update this file, increment the version per
semantic versioning (MAJOR for principle removals/redefinitions, MINOR
for new principles or material expansions, PATCH for clarifications),
and update `LAST_AMENDED_DATE`.

**Compliance**: Every feature plan MUST include a Constitution Check
that verifies alignment with these principles before implementation
begins.

**Version**: 1.0.0 | **Ratified**: 2026-02-22 | **Last Amended**: 2026-02-22
