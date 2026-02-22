# Tasks: Currere Web

**Input**: Design documents from `/specs/001-currere-web-app/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/api-v1.md, design-tokens.md

**Tests**: Included per constitution principle V (Pragmatic Testing). Focus on API endpoints, deduplication logic, AI structured output schemas. No Inertia rendering tests.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Backend**: `app/`, `database/`, `routes/`, `config/`, `tests/` at repository root
- **Frontend**: `resources/js/` at repository root

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install new dependencies and configure packages

- [x] T001 Install Laravel Sanctum, publish its migration, and configure `auth:sanctum` middleware in `bootstrap/app.php`
- [x] T002 Install Laravel AI SDK (`laravel/ai`), publish `config/ai.php`, add `ANTHROPIC_API_KEY` to `.env.example`
- [x] T003 [P] Install frontend dependencies: `npm i layerchart@next qrcode && npm i -D @types/qrcode`
- [x] T004 [P] Apply Currere dark theme CSS variables in `resources/css/app.css` per `design-tokens.md` (backgrounds, accent, text, chart colors, functional colors)
- [x] T005 Create `routes/api.php` with `auth:sanctum` middleware group under `/v1` prefix, register it in `bootstrap/app.php` via `withRouting(api:)`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database schema, Eloquent models, factories, API resources, and navigation — shared by all user stories

**CRITICAL**: No user story work can begin until this phase is complete

- [x] T006 Create `runs` table migration in `database/migrations/` with all columns from data-model.md (user_id FK, start_time, end_time, distance_km, duration_seconds, steps, avg_heart_rate, avg_pace_seconds_per_km) and composite unique index on (user_id, start_time, distance_km)
- [x] T007 [P] Create `heart_rate_samples` table migration in `database/migrations/` with run_id FK (cascade delete), timestamp (datetime type, UTC), bpm, and composite index on (run_id, timestamp)
- [x] T008 [P] Create `pace_splits` table migration in `database/migrations/` with run_id FK (cascade delete), kilometer_number, split_time_seconds, pace_seconds_per_km, is_partial, partial_distance_km, and composite index on (run_id, kilometer_number)
- [x] T009 Add `HasApiTokens` trait and `runs()` hasMany relationship to `app/Models/User.php`
- [x] T010 Create Run model in `app/Models/Run.php` with belongsTo(User), hasMany(HeartRateSample), hasMany(PaceSplit), casts for start_time/end_time as datetime, and fillable attributes
- [x] T011 [P] Create HeartRateSample model in `app/Models/HeartRateSample.php` with belongsTo(Run), timestamp cast, and fillable attributes
- [x] T012 [P] Create PaceSplit model in `app/Models/PaceSplit.php` with belongsTo(Run), is_partial boolean cast, and fillable attributes
- [x] T013 [P] Create RunFactory in `database/factories/RunFactory.php` with realistic running data defaults (5-42km, 20-240min, 100-200bpm)
- [x] T014 [P] Create HeartRateSampleFactory in `database/factories/HeartRateSampleFactory.php`
- [x] T015 [P] Create PaceSplitFactory in `database/factories/PaceSplitFactory.php`
- [x] T016 Create RunResource in `app/Http/Resources/RunResource.php` (summary fields for list view) and RunDetailResource in `app/Http/Resources/RunDetailResource.php` (includes heart_rate_samples and pace_splits)
- [x] T017 [P] Create HeartRateSampleResource in `app/Http/Resources/HeartRateSampleResource.php` and PaceSplitResource in `app/Http/Resources/PaceSplitResource.php`
- [x] T018 Create RunSeeder in `database/seeders/RunSeeder.php` that generates 20 runs with HR samples and pace splits for the first user, register in DatabaseSeeder
- [x] T019 Update sidebar navigation in `resources/js/components/AppSidebar.svelte` (or NavMain.svelte) to add Run Diary, AI Analysis, and API Tokens menu items with appropriate icons from lucide-svelte

**Checkpoint**: Foundation ready — models, migrations, factories, resources, and navigation in place

---

## Phase 3: User Story 1 — Run Data Sync via API (Priority: P1) MVP

**Goal**: Android app can sync runs to the backend via REST API with deduplication

**Independent Test**: `curl` POST a run to `/api/v1/runs` with a Sanctum token, verify 201 response. POST the same run again, verify 200 "already synced" response.

### Tests for User Story 1

- [x] T020 [P] [US1] Create StoreRunTest in `tests/Feature/Api/StoreRunTest.php` — test 201 on valid run with HR samples + pace splits, test 200 on duplicate (same start_time + distance_km), test 422 on invalid data, test 401 without token, test user scoping (cannot see other user's runs)
- [x] T021 [P] [US1] Create BatchStoreRunTest in `tests/Feature/Api/BatchStoreRunTest.php` — test batch of 3 runs returns created/skipped counts, test mixed new + duplicate batch, test 422 on invalid run in batch
- [x] T022 [P] [US1] Create IndexRunTest in `tests/Feature/Api/IndexRunTest.php` — test paginated list ordered by start_time desc, test user scoping, test 401 without token
- [x] T023 [P] [US1] Create ShowRunTest in `tests/Feature/Api/ShowRunTest.php` — test full run detail with HR samples and pace splits, test 404 for other user's run, test 401 without token

### Implementation for User Story 1

- [x] T024 [P] [US1] Create StoreRunRequest in `app/Http/Requests/Api/StoreRunRequest.php` with all validation rules from data-model.md (including nested heart_rate_samples.* and pace_splits.* rules)
- [x] T025 [P] [US1] Create BatchStoreRunRequest in `app/Http/Requests/Api/BatchStoreRunRequest.php` with `runs` array validation wrapping StoreRunRequest rules
- [x] T026 [US1] Create StoreRunController (invokable) in `app/Http/Controllers/Api/V1/StoreRunController.php` — check for duplicate (user_id + start_time + distance_km), skip with 200 if exists, otherwise create run + bulk insert HR samples + pace splits in transaction, return 201 with RunResource
- [x] T027 [US1] Create BatchStoreRunController (invokable) in `app/Http/Controllers/Api/V1/BatchStoreRunController.php` — iterate runs, apply same dedup logic per run, return summary with created/skipped counts and per-run results
- [x] T028 [P] [US1] Create IndexRunController (invokable) in `app/Http/Controllers/Api/V1/IndexRunController.php` — return paginated RunResource collection for auth user, ordered by start_time desc
- [x] T029 [P] [US1] Create ShowRunController (invokable) in `app/Http/Controllers/Api/V1/ShowRunController.php` — return RunDetailResource with eager-loaded heartRateSamples and paceSplits, scoped to auth user
- [x] T030 [US1] Register all API v1 routes in `routes/api.php`: POST /runs, POST /runs/batch, GET /runs, GET /runs/{run}

**Checkpoint**: API fully functional — Android app can sync and retrieve runs

---

## Phase 4: User Story 2 — Run Diary Dashboard (Priority: P2)

**Goal**: Logged-in user sees paginated, sortable, filterable run list as their home page

**Independent Test**: Log in, see run list with correct formatting (2 decimal km, h:mm:ss duration, m:ss/km pace). Filter by date range, sort by distance.

### Tests for User Story 2

- [x] T031 [US2] Create dashboard index test in `tests/Feature/Runs/IndexTest.php` — test authenticated access returns runs page with paginated data, test date range filtering, test sort parameter (e.g. `?sort=distance_km&direction=desc`), test unauthenticated redirect to login

### Implementation for User Story 2

- [x] T032 [US2] Create Runs/IndexController (invokable) in `app/Http/Controllers/Runs/IndexController.php` — query user's runs with optional date_from/date_to filters and sort parameter, paginate, return Inertia render with RunResource collection
- [x] T033 [US2] Create RunTable.svelte component in `resources/js/components/runs/RunTable.svelte` — sortable columns (date, distance, duration, pace, HR), formatted display (km to 2 decimals, duration as h:mm:ss, pace as m:ss/km) using TypeScript formatting utilities, click row to navigate to detail. Note: all pace/duration formatting is frontend-only (no backend helpers).
- [x] T034 [P] [US2] Create RunFilters.svelte component in `resources/js/components/runs/RunFilters.svelte` — date range picker (from/to inputs), applies filters via Inertia router visit with query params
- [x] T035 [US2] Create runs/Index.svelte page in `resources/js/pages/runs/Index.svelte` — uses AppLayout, includes RunFilters + RunTable + pagination, shows empty state with connect-Android-app guidance when no runs
- [x] T036 [US2] Update `routes/web.php` — change `/dashboard` route to render runs/Index (replacing existing Dashboard.svelte), add route name `dashboard`

**Checkpoint**: Web dashboard shows paginated run diary with filtering and sorting

---

## Phase 5: User Story 3 — Run Detail View (Priority: P3)

**Goal**: Full run detail page with summary stats, HR chart, pace chart, splits table, and delete functionality

**Independent Test**: Click a run from the diary, see summary stats + charts + splits table. Delete a run and verify it's gone.

### Tests for User Story 3

- [x] T037 [P] [US3] Create run show test in `tests/Feature/Runs/ShowTest.php` — test authenticated access returns run detail with HR samples and pace splits, test 403 for other user's run
- [x] T038 [P] [US3] Create run destroy test in `tests/Feature/Runs/DestroyTest.php` — test delete cascades to HR samples and pace splits, test redirect after delete, test 403 for other user's run

### Implementation for User Story 3

- [x] T039 [US3] Create Runs/ShowController (invokable) in `app/Http/Controllers/Runs/ShowController.php` — load run with heartRateSamples and paceSplits, verify ownership, return Inertia render
- [x] T040 [P] [US3] Create Runs/DestroyController (invokable) in `app/Http/Controllers/Runs/DestroyController.php` — verify ownership, delete run (cascade handles related records), redirect to dashboard
- [x] T041 [US3] Create RunStats.svelte component in `resources/js/components/runs/RunStats.svelte` — displays distance, duration, pace, steps, avg HR as stat cards
- [x] T042 [P] [US3] Create HeartRateChart.svelte component in `resources/js/components/runs/HeartRateChart.svelte` — LayerChart line chart, HR (bpm) over elapsed time, using `--chart-heart-rate` color, handles empty data with "No heart rate data" message
- [x] T043 [P] [US3] Create PaceChart.svelte component in `resources/js/components/runs/PaceChart.svelte` — LayerChart line chart, pace over elapsed time, using `--chart-pace` color
- [x] T044 [US3] Create PaceSplitsTable.svelte component in `resources/js/components/runs/PaceSplitsTable.svelte` — km number, split pace with colored bar (green for fast/red for slow using `--split-fast`/`--split-slow`), cumulative time, partial split indicator
- [x] T045 [US3] Create runs/Show.svelte page in `resources/js/pages/runs/Show.svelte` — uses AppLayout, includes RunStats + HeartRateChart + PaceChart + PaceSplitsTable + Delete button with confirmation dialog
- [x] T046 [US3] Add run show (`GET /runs/{run}`) and destroy (`DELETE /runs/{run}`) routes in `routes/web.php`

**Checkpoint**: Full run detail view with charts, splits table, and delete functionality

---

## Phase 6: User Story 4 — API Token Management (Priority: P4)

**Goal**: Users can generate Sanctum API tokens with QR codes, view active tokens, and revoke them

**Independent Test**: Generate a token, verify QR code contains JSON with `token` and `base_url`. Use the token in an API call. Revoke it and verify 401 on subsequent calls.

### Tests for User Story 4

- [x] T047 [US4] Create token management test in `tests/Feature/Settings/TokenTest.php` — test token creation returns plain text token, test token list shows names but not values, test token revocation, test revoked token returns 401 on API call

### Implementation for User Story 4

- [x] T048 [US4] Create StoreTokenRequest in `app/Http/Requests/Settings/StoreTokenRequest.php` with name validation (required, string, max:255)
- [x] T049 [US4] Create TokenController in `app/Http/Controllers/Settings/TokenController.php` — index (list tokens via Inertia), store (createToken, return plain text + base URL), destroy (revoke token)
- [x] T050 [US4] Create TokenForm.svelte component in `resources/js/components/tokens/TokenForm.svelte` — input for token name, submit button, uses Wayfinder route for form action
- [x] T051 [P] [US4] Create TokenDisplay.svelte component in `resources/js/components/tokens/TokenDisplay.svelte` — shows plain text token (copy button) + QR code via `qrcode` npm package (JSON payload: `{ "token": "...", "base_url": "..." }`)
- [x] T052 [P] [US4] Create TokenList.svelte component in `resources/js/components/tokens/TokenList.svelte` — lists active tokens with name, created date, last used date, and revoke button with confirmation
- [x] T053 [US4] Create settings/Tokens.svelte page in `resources/js/pages/settings/Tokens.svelte` — uses settings Layout, includes TokenForm + TokenDisplay (conditional) + TokenList
- [x] T054 [US4] Add token routes in `routes/settings.php`: GET /settings/tokens, POST /settings/tokens, DELETE /settings/tokens/{token}

**Checkpoint**: Full token lifecycle — generate, view QR, list, revoke

---

## Phase 7: User Story 5 — AI Training Analysis (Priority: P5)

**Goal**: 6 AI analysis skills using Laravel AI SDK with structured output, displayed as formatted cards

**Independent Test**: Select "Monthly Training Summary" skill, verify structured result card with all expected fields. Verify insufficient data message with <3 runs.

### Tests for User Story 5

- [x] T055 [US5] Create AI skill execution test in `tests/Feature/Analysis/RunSkillTest.php` — use Agent::fake() to mock AI responses, test each skill returns expected structured output fields, test insufficient data message for <3 runs, test AI provider error returns user-friendly message ("Analysis temporarily unavailable"), test 401 for unauthenticated users

### Implementation for User Story 5 — AI Tools

- [x] T056 [P] [US5] Create FetchRecentRunsTool in `app/Ai/Tools/FetchRecentRunsTool.php` — Tool that accepts user_id and days parameter, returns recent runs as JSON (used by MonthlyTrainingSummary, PerformanceTrend, AnomalyDetection, TrainingRecommendation)
- [x] T057 [P] [US5] Create FetchRunStatsTool in `app/Ai/Tools/FetchRunStatsTool.php` — Tool that accepts user_id and period, returns aggregated stats (total distance, total time, run count, avg pace, best pace) as JSON (used by RacePacePredictor)
- [x] T058 [P] [US5] Create FetchHeartRateDataTool in `app/Ai/Tools/FetchHeartRateDataTool.php` — Tool that accepts user_id and days, returns HR samples with max observed HR across all runs (used by HeartRateZoneAnalysis)

### Implementation for User Story 5 — AI Agents

- [x] T059 [P] [US5] Create MonthlyTrainingSummaryAgent in `app/Ai/Agents/MonthlyTrainingSummaryAgent.php` — structured output: total_distance_km, total_time_seconds, run_count, avg_pace, previous_month_comparison, summary_text
- [x] T060 [P] [US5] Create PerformanceTrendAgent in `app/Ai/Agents/PerformanceTrendAgent.php` — structured output: trend_direction, pace_change_per_week, data_points array, analysis_text
- [x] T061 [P] [US5] Create RacePacePredictorAgent in `app/Ai/Agents/RacePacePredictorAgent.php` — structured output: predictions array (distance, predicted_time, confidence), explanation_text
- [x] T062 [P] [US5] Create HeartRateZoneAnalysisAgent in `app/Ai/Agents/HeartRateZoneAnalysisAgent.php` — structured output: zones array (zone_number, name, min_bpm, max_bpm, time_seconds, percentage), recommendation_text. Uses max observed HR for zone calculation.
- [x] T063 [P] [US5] Create AnomalyDetectionAgent in `app/Ai/Agents/AnomalyDetectionAgent.php` — structured output: flagged_runs array (run_id, date, anomaly_type, description), summary_text
- [x] T064 [P] [US5] Create TrainingRecommendationAgent in `app/Ai/Agents/TrainingRecommendationAgent.php` — structured output: recommendation_type, reasoning, suggested_distance_km, suggested_pace, summary_text

### Implementation for User Story 5 — Controllers & Frontend

- [x] T065 [US5] Create Analysis/IndexController (invokable) in `app/Http/Controllers/Analysis/IndexController.php` — returns Inertia page with list of available skills and user's run count for minimum-data check
- [x] T066 [US5] Create Analysis/RunSkillController (invokable) in `app/Http/Controllers/Analysis/RunSkillController.php` — validates skill name, checks minimum run count (3), instantiates agent with user context, returns structured JSON response, handles AI provider errors gracefully
- [x] T067 [US5] Create SkillSelector.svelte component in `resources/js/components/analysis/SkillSelector.svelte` — grid of skill cards with name, description, icon, click to trigger analysis, loading state with skeleton
- [x] T068 [P] [US5] Create SkillResultCard.svelte component in `resources/js/components/analysis/SkillResultCard.svelte` — renders structured AI output as formatted card, adapts layout per skill type (stats grid for summary, list for anomalies, predictions table for race pace)
- [x] T069 [US5] Create analysis/Index.svelte page in `resources/js/pages/analysis/Index.svelte` — uses AppLayout, includes SkillSelector + SkillResultCard (shown after skill execution), insufficient data message when <3 runs
- [x] T070 [US5] Add analysis routes in `routes/web.php`: GET /analysis, POST /analysis/{skill}

**Checkpoint**: All 6 AI skills functional with structured output cards

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Code quality, formatting, and end-to-end validation

- [x] T071 Run `vendor/bin/pint --dirty --format agent` on all modified PHP files. Verify all new PHP files include `declare(strict_types=1)` per constitution.
- [x] T072 [P] Run `npm run format` and `npm run lint` on all frontend files
- [x] T073 Run full test suite with `php artisan test --compact` and fix any failures
- [x] T074 [P] Run `npm run build` to verify frontend compiles without errors
- [x] T075 Validate quickstart.md setup steps work on a clean database (`php artisan migrate:fresh --seed`)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 completion — BLOCKS all user stories
- **US1 (Phase 3)**: Depends on Phase 2 — the MVP
- **US2 (Phase 4)**: Depends on Phase 2. Benefits from US1 data but is independently testable with seeded data.
- **US3 (Phase 5)**: Depends on Phase 2. Requires US2 for navigation but can be developed independently.
- **US4 (Phase 6)**: Depends on Phase 2 (Sanctum). Independent of US1-3.
- **US5 (Phase 7)**: Depends on Phase 2 (models). Independent of US2-4 but benefits from US1 data.
- **Polish (Phase 8)**: Depends on all desired user stories being complete

### User Story Dependencies

- **US1 (P1)**: No dependencies on other stories — pure API, testable with curl/Pest
- **US2 (P2)**: No hard dependencies — seeded data sufficient for testing
- **US3 (P3)**: No hard dependencies — can navigate directly to `/runs/{id}`
- **US4 (P4)**: No hard dependencies — Sanctum token management is self-contained
- **US5 (P5)**: No hard dependencies — AI agents query DB directly, can use seeded data

### Within Each User Story

- Tests written first, verify they fail
- Form Requests before Controllers
- Controllers before Frontend components
- Components before Page assembly
- Routes registered last (wires everything together)

### Parallel Opportunities

- Phase 1: T003 and T004 can run in parallel with T001/T002/T005
- Phase 2: T007, T008 parallel with T006; T011, T012 parallel with T010; T013-T015 all parallel; T016-T017 parallel
- Phase 3: All 4 test tasks parallel; T024-T025 parallel; T028-T029 parallel
- Phase 4: T034 parallel with T033
- Phase 5: T037-T038 parallel; T042-T043 parallel
- Phase 6: T051-T052 parallel
- Phase 7: T056-T058 all parallel; T059-T064 all parallel (all agents); T068 parallel with T067

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1 (API sync)
4. **STOP and VALIDATE**: Test API with curl per quickstart.md
5. Android app can begin syncing data

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1 (API sync) → Android app can sync (MVP!)
3. US4 (Token management) → Users can generate tokens from web
4. US2 (Run diary) → Web dashboard shows runs
5. US3 (Run detail) → Charts and splits visible
6. US5 (AI analysis) → Training insights available
7. Polish → Code quality pass

### Recommended Order

US4 is listed as P4 by user story priority but should be implemented early (after US1) since the Android app needs a token to authenticate. The suggested implementation order is: US1 → US4 → US2 → US3 → US5.

---

## Notes

- [P] tasks = different files, no dependencies on incomplete tasks
- [Story] label maps task to specific user story for traceability
- Each user story is independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Constitution V: Tests focus on API endpoints, dedup logic, AI structured output — no Inertia rendering tests
- Formatting decision: All pace/duration display formatting (h:mm:ss, m:ss/km) is handled in TypeScript frontend utilities, not PHP backend helpers. No unit test for Run model formatting needed.
