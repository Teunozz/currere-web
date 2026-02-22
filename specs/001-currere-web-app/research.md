# Research: Currere Web

**Branch**: `001-currere-web-app` | **Date**: 2026-02-22

## Charting Library

**Decision**: LayerChart v2 (`layerchart@next`)

**Rationale**: shadcn-svelte's Chart component is built on top of LayerChart v2, providing first-class integration with the existing UI framework — theming via CSS variables, matching tooltip components, and config-based color tokens. LayerChart v2 is Svelte 5 native (uses runes, not stores), built on Layer Cake + D3, and handles thousands of data points via SVG with Canvas fallback.

**Alternatives considered**:
- **svelte-chartjs** — Last published ~2 years ago, wrapper around Chart.js, no native runes support, styling clashes with shadcn. Rejected.
- **Pancake (@sveltejs/pancake)** — Abandoned 4+ years ago, not Svelte 5 compatible. Rejected.
- **Unovis (@unovis/svelte)** — Explicitly dropped Svelte 5 support in v1.5.0. Rejected.

**Caveats**: LayerChart v2 is pre-release (`@next` tag). shadcn-svelte tracks it, so upgrade paths are documented.

**Install**: `npm i layerchart@next`

## QR Code Generation

**Decision**: `qrcode` npm package (framework-agnostic)

**Rationale**: Pure JavaScript library with no Svelte dependency. Generates QR codes as SVG strings via `QRCode.toString(data, { type: 'svg' })`, rendered with `{@html svgString}`. Works both client-side and server-side. ~4M weekly npm downloads, mature and well-maintained.

**Alternatives considered**:
- **@castlenine/svelte-qrcode** — Svelte-native but unclear Svelte 5 runes compatibility. Rejected.
- **svelte-qrcode-image** — Wraps node-qrcode internally, unnecessary abstraction. Rejected.

**Install**: `npm i qrcode && npm i -D @types/qrcode`

## Laravel AI SDK

**Decision**: Use `laravel/ai` package (officially released, documented)

**Rationale**: The Laravel AI SDK is a real, production-ready package with full documentation at laravel.com/docs/12.x/ai-sdk. It provides exactly the patterns needed: Agent classes, Tool classes, HasStructuredOutput interface, and first-class Pest testing support via `::fake()`.

**Key API surface**:

- **Agents**: PHP classes implementing `Agent` contract with `Promptable` trait. Created via `php artisan make:agent AgentName --structured`.
- **Tools**: PHP classes implementing `Tool` contract with `description()`, `schema()`, `handle()` methods. Created via `php artisan make:tool ToolName`.
- **Structured output**: `HasStructuredOutput` interface with `schema(JsonSchema $schema): array` method. Response is array-accessible.
- **Provider config**: Anthropic configured via `ANTHROPIC_API_KEY` env var and `config/ai.php`. Provider set via `#[Provider(Lab::Anthropic)]` attribute or at invocation time.
- **Testing**: `Agent::fake(['response'])` with assertions like `Agent::assertPrompted()`.
- **Invocation**: `(new Agent)->prompt('...')` returns response. Supports `->stream()`, `->queue()`, `->broadcastOnQueue()`.

**Caveats**: None — the package is officially released and documented. Use `#[Provider(Lab::Anthropic)]` attribute on each agent class.

## Existing Project Structure

**Decision**: Use standard Laravel structure (no new base folders needed)

**Key findings**:
- Starter kit provides: auth (Fortify), settings pages (profile, password, 2FA, appearance), sidebar layout, 20+ shadcn-svelte UI components
- Database: users, sessions, password_resets, cache, jobs tables exist
- `bootstrap/app.php` configures middleware declaratively (Laravel 12 style)
- Wayfinder configured in `vite.config.ts` with `formVariants: true`
- Pest configured with `RefreshDatabase` trait, 13 auth tests already exist
- No `routes/api.php` exists yet — needs to be created
- No Sanctum configured yet — needs `composer require laravel/sanctum` and setup

## Sanctum API Token Setup

**Decision**: Use Laravel Sanctum's built-in token management

**Rationale**: Sanctum provides `HasApiTokens` trait for the User model, `createToken()` / `tokens()` / `currentAccessToken()->delete()` methods, and `auth:sanctum` middleware. The `personal_access_tokens` table is created by Sanctum's migration. This is the standard Laravel approach per the constitution.

**Install**: `composer require laravel/sanctum` (may already be included as a framework dependency in Laravel 12).
