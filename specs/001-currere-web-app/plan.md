# Implementation Plan: Currere Web

**Branch**: `001-currere-web-app` | **Date**: 2026-02-22 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-currere-web-app/spec.md`

## Summary

Build the Currere Web application: a Laravel 12 backend with REST API for run data sync from an Android app, a Svelte 5 web dashboard for viewing runs with charts, and AI-powered training analysis using the Laravel AI SDK. The project extends the existing Svelte starter kit (Fortify auth, Inertia 2, shadcn-svelte) with new models (Run, HeartRateSample, PaceSplit), API endpoints under `/api/v1/`, Sanctum token management with QR codes, and 6 AI agent classes for training insights.

## Technical Context

**Language/Version**: PHP 8.4 + TypeScript 5.7
**Primary Dependencies**: Laravel 12, Inertia 2, Svelte 5, Fortify, Sanctum, Laravel AI SDK, LayerChart v2, qrcode (npm)
**Storage**: SQLite (development), PostgreSQL or MySQL (production)
**Testing**: Pest 4 with pest-plugin-laravel
**Target Platform**: Web (modern browsers, latest 2 versions)
**Project Type**: Web application (API + dashboard)
**Performance Goals**: <2s diary page load, <5s sync-to-dashboard visibility, <30s AI analysis
**Constraints**: Solo project, simplicity over scale, YAGNI
**Scale/Scope**: Single user (possibly a few), ~1,000 runs, 4 dashboard screens + API

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Evidence |
|-----------|--------|----------|
| I. Laravel Conventions First | PASS | Sanctum for API auth, Fortify for web auth, Eloquent models, Form Requests, API Resources, invokable controllers |
| II. Extend the Starter Kit | PASS | All new pages extend existing AppLayout, use shadcn-svelte components, Wayfinder for route references |
| III. API-First Data Layer | PASS | REST API under `/api/v1/`, Sanctum Bearer tokens, Eloquent API Resources, invokable controllers for single-action endpoints |
| IV. AI via Laravel AI SDK | PASS | All 6 AI skills built as Agent classes with HasStructuredOutput, Tools for DB queries, `#[Provider(Lab::Anthropic)]` |
| V. Pragmatic Testing | PASS | Pest tests focus on API endpoints, deduplication logic, AI structured output schemas. No Inertia rendering tests. |
| VI. Simplicity for Solo Use | PASS | No multi-tenancy, no horizontal scaling, no premature abstractions. Direct Eloquent queries, simple controllers. |

**Post-design re-check**: PASS — no violations introduced during design.

## Project Structure

### Documentation (this feature)

```text
specs/001-currere-web-app/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0: library/SDK research
├── data-model.md        # Phase 1: entity definitions
├── quickstart.md        # Phase 1: setup guide
├── contracts/
│   └── api-v1.md        # Phase 1: REST API contract
└── tasks.md             # Phase 2: task list (via /speckit.tasks)
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── User.php                    # Extend with HasApiTokens
│   ├── Run.php                     # New
│   ├── HeartRateSample.php         # New
│   └── PaceSplit.php               # New
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/
│   │   │   ├── PingController.php            # GET /api/v1/ping (invokable)
│   │   │   └── BatchStoreRunController.php   # POST /api/v1/runs/batch (invokable)
│   │   ├── Runs/
│   │   │   ├── IndexController.php           # Dashboard/diary (invokable)
│   │   │   ├── ShowController.php            # Run detail (invokable)
│   │   │   └── DestroyController.php         # Delete run (invokable)
│   │   ├── Analysis/
│   │   │   ├── IndexController.php           # AI analysis page (invokable)
│   │   │   └── RunSkillController.php        # Execute AI skill (invokable)
│   │   └── Settings/
│   │       └── TokenController.php           # Token CRUD (resourceful)
│   ├── Requests/
│   │   ├── Api/
│   │   │   └── BatchStoreRunRequest.php
│   │   └── Settings/
│   │       └── StoreTokenRequest.php
│   └── Resources/
│       └── RunResource.php
├── Ai/
│   ├── Agents/
│   │   ├── MonthlyTrainingSummaryAgent.php
│   │   ├── PerformanceTrendAgent.php
│   │   ├── RacePacePredictorAgent.php
│   │   ├── HeartRateZoneAnalysisAgent.php
│   │   ├── AnomalyDetectionAgent.php
│   │   └── TrainingRecommendationAgent.php
│   └── Tools/
│       ├── FetchRecentRunsTool.php
│       ├── FetchRunStatsTool.php
│       └── FetchHeartRateDataTool.php

database/
├── migrations/
│   ├── xxxx_create_runs_table.php
│   ├── xxxx_create_heart_rate_samples_table.php
│   └── xxxx_create_pace_splits_table.php
├── factories/
│   ├── RunFactory.php
│   ├── HeartRateSampleFactory.php
│   └── PaceSplitFactory.php
└── seeders/
    └── RunSeeder.php

resources/js/
├── pages/
│   ├── runs/
│   │   ├── Index.svelte              # Run diary (replaces Dashboard.svelte)
│   │   └── Show.svelte               # Run detail with charts
│   ├── analysis/
│   │   └── Index.svelte              # AI analysis page
│   └── settings/
│       └── Tokens.svelte             # API token management
├── components/
│   ├── runs/
│   │   ├── RunTable.svelte           # Paginated, sortable run table
│   │   ├── RunFilters.svelte         # Date range filter
│   │   ├── RunStats.svelte           # Summary stats card
│   │   ├── HeartRateChart.svelte     # HR line chart (LayerChart)
│   │   ├── PaceChart.svelte          # Pace line chart (LayerChart)
│   │   └── PaceSplitsTable.svelte    # Splits table with colored bars
│   ├── analysis/
│   │   ├── SkillSelector.svelte      # Skill picker
│   │   └── SkillResultCard.svelte    # Structured result display
│   └── tokens/
│       ├── TokenForm.svelte          # Generate token form
│       ├── TokenDisplay.svelte       # Token + QR code display
│       └── TokenList.svelte          # Active tokens list

routes/
├── web.php                           # Add run + analysis + token routes
├── api.php                           # New: API v1 routes
└── settings.php                      # Add token routes

config/
└── ai.php                            # New: AI SDK configuration

tests/
├── Feature/
│   ├── Api/
│   │   ├── BatchStoreRunTest.php
│   │   └── V1/PingTest.php
│   ├── Runs/
│   │   ├── IndexTest.php
│   │   ├── ShowTest.php
│   │   └── DestroyTest.php
│   ├── Analysis/
│   │   └── RunSkillTest.php
│   └── Settings/
│       └── TokenTest.php
```

**Structure Decision**: Standard Laravel structure. All new code goes into existing `app/`, `database/`, `resources/js/`, `routes/`, `config/`, and `tests/` directories. No new base folders. API controllers namespaced under `Api/V1/` for versioning. AI agents under `Ai/Agents/` and tools under `Ai/Tools/`.

**Formatting Decision**: Pace/duration display formatting (h:mm:ss, m:ss/km, 2-decimal km) is handled in TypeScript frontend utilities, not PHP backend helpers. The backend sends raw numeric values (seconds, km) via API Resources, and Svelte components format them for display. No `RunTest.php` unit test needed.

## Complexity Tracking

No constitution violations. No complexity justifications needed.
