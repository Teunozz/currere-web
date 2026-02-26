# Feature Specification: Currere Web

**Feature Branch**: `001-currere-web-app`
**Created**: 2026-02-22
**Status**: Draft
**Input**: User description: "Build Currere Web — a Laravel 12 backend + web dashboard that serves as the companion to a Currere Android app. The Android app syncs running data to this backend via a REST API. The web dashboard displays the same data with additional AI-powered analysis."

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Run Data Sync via API (Priority: P1)

A runner finishes a run on the Android app. The app syncs the run data (distance, duration, pace, steps, heart rate, heart rate samples, pace splits) to the Currere Web backend via a REST API. The runner can also batch-sync multiple past runs at once. If a run has already been synced, the system skips it entirely rather than creating a duplicate. Runs are immutable once synced — to correct bad data, the user deletes the run from the web dashboard and re-syncs from the Android app.

**Why this priority**: Without data ingestion, no other feature (dashboard, analysis) is possible. The API is the foundation.

**Independent Test**: Send a POST request with run data to the API and verify the data is stored correctly. Send the same data again and verify no duplicate is created.

**Acceptance Scenarios**:

1. **Given** a user with a valid API token, **When** they POST a run with heart rate samples and pace splits to `/api/v1/runs`, **Then** the run and all associated data are stored and a 201 response with the run resource is returned.
2. **Given** a user with a valid API token, **When** they POST a batch of 5 runs to `/api/v1/runs/batch`, **Then** all 5 runs are stored and a 201 response with a summary (created count, skipped count) is returned.
3. **Given** a run already exists with the same `start_time` and `distance_km` for the user, **When** they POST the same run again, **Then** the system skips the duplicate and returns a 200 response indicating the run was already synced.
4. **Given** a request with an invalid or missing Bearer token, **When** any API endpoint is called, **Then** a 401 Unauthorized response is returned.
5. **Given** a user with a valid API token, **When** they GET `/api/v1/runs`, **Then** a paginated list of their runs is returned, ordered by most recent.
6. **Given** a user with a valid API token, **When** they GET `/api/v1/runs/{id}` for a run they own, **Then** the full run detail including heart rate samples and pace splits is returned.

---

### User Story 2 — Run Diary Dashboard (Priority: P2)

After logging in, the runner sees a paginated list of all their synced runs ordered by most recent. Each row shows date/time, distance, duration, average pace, and average heart rate. The runner can filter by date range and sort by any column.

**Why this priority**: The dashboard is the primary reason a user visits the web app. It provides immediate value by giving a clear overview of training history.

**Independent Test**: Log in, verify the run list appears with correct data, apply a date filter and verify results narrow correctly.

**Acceptance Scenarios**:

1. **Given** a logged-in user with 25 runs, **When** they visit the dashboard, **Then** they see a paginated list of runs ordered by most recent, with date, distance (2 decimals), duration (h:mm:ss), pace (m:ss/km), and heart rate (bpm).
2. **Given** a logged-in user, **When** they set a date range filter from January 1 to January 31, **Then** only runs within that range are displayed.
3. **Given** a logged-in user, **When** they click the "Distance" column header, **Then** runs are sorted by distance (toggling ascending/descending).
4. **Given** a logged-in user with no runs, **When** they visit the dashboard, **Then** they see an empty state with guidance on how to connect the Android app.

---

### User Story 3 — Run Detail View (Priority: P3)

The runner clicks a run from the diary to see a full detail page. The page shows summary stats, a heart rate chart over elapsed time, a pace chart over elapsed time, and a pace splits table with colored pace bars and cumulative time.

**Why this priority**: Detailed run analysis is the second most valuable feature for a runner reviewing their training.

**Independent Test**: Navigate to a run detail page and verify all charts render with correct data and the pace splits table is complete.

**Acceptance Scenarios**:

1. **Given** a logged-in user viewing the run diary, **When** they click a run row, **Then** they are navigated to a detail page showing distance, duration, pace, steps, and average heart rate.
2. **Given** a run with heart rate samples, **When** the detail page loads, **Then** a line chart of heart rate (bpm) over elapsed time is displayed.
3. **Given** a run with pace splits, **When** the detail page loads, **Then** a line chart of pace over elapsed time and a splits table (km number, split pace with colored bar, cumulative time) are displayed.
4. **Given** a run with a partial last split (e.g., 0.4 km), **When** the splits table renders, **Then** the partial split is clearly indicated and its pace is shown as the adjusted per-km pace.
5. **Given** a logged-in user viewing a run detail page, **When** they click "Delete Run" and confirm, **Then** the run and all associated data (heart rate samples, pace splits) are permanently deleted.

---

### User Story 4 — API Token Management (Priority: P4)

From the settings page, the runner can generate new API tokens with a descriptive name (e.g., "Samsung Galaxy S25"). After generation, the token is displayed once in plain text AND as a QR code containing a JSON payload with the token and API base URL. The runner can also view active tokens and revoke them.

**Why this priority**: Token management is required for the Android app to connect, but the user only needs it during initial setup.

**Independent Test**: Generate a token, verify it appears in plain text and as a QR code. Verify the QR code payload contains the expected JSON. Revoke the token and verify it no longer works for API calls.

**Acceptance Scenarios**:

1. **Given** a logged-in user on the settings page, **When** they enter a token name and click "Generate Token", **Then** a new API token is created and displayed once in plain text.
2. **Given** a newly generated token, **When** the token display appears, **Then** a QR code is shown containing a JSON payload with `token` and `base_url` fields.
3. **Given** a logged-in user on the settings page, **When** they view the token list, **Then** they see all active tokens with their names and creation dates (but NOT the token values).
4. **Given** a logged-in user, **When** they click "Revoke" on a token, **Then** the token is deleted and any subsequent API calls with that token return 401.

---

### User Story 5 — AI Training Analysis (Priority: P5)

The runner visits an AI Analysis page and selects from a list of analysis skills: monthly training summary, performance trend, race pace prediction, heart rate zone analysis, anomaly detection, and training recommendations. Each skill runs against their run data and returns structured results displayed as a formatted card.

**Why this priority**: AI analysis is a differentiating feature but depends on having run data (P1) and a working dashboard (P2) first.

**Independent Test**: Select a skill, trigger analysis, and verify structured results appear as a formatted card with the expected data fields.

**Acceptance Scenarios**:

1. **Given** a logged-in user with at least 10 runs over the past 30 days, **When** they select "Monthly Training Summary", **Then** a card displays total distance, total time, number of runs, average pace, comparison to the previous month, and a natural language summary.
2. **Given** a logged-in user with at least 4 weeks of runs, **When** they select "Performance Trend", **Then** a card displays trend direction (faster/slower/stable), pace change per week, and data points suitable for visualization.
3. **Given** a logged-in user with recent training data, **When** they select "Race Pace Prediction", **Then** a card displays estimated finish times for 5K, 10K, half marathon, and marathon with confidence levels and explanation.
4. **Given** a logged-in user with heart rate data, **When** they select "Heart Rate Zone Analysis", **Then** a card displays time per zone (5 standard zones based on estimated max HR), distribution percentages, and a recommendation on training intensity balance. Max HR is estimated from the user's highest recorded heart rate across all runs.
5. **Given** a logged-in user, **When** they select "Anomaly Detection", **Then** a card lists any flagged runs with the specific anomaly reason (e.g., "Heart rate 20% higher than usual for this pace").
6. **Given** a logged-in user, **When** they select "Training Recommendation", **Then** a card displays a suggested next workout type, reasoning, and suggested distance/pace.
7. **Given** a user with fewer than 3 runs, **When** they attempt any AI skill, **Then** they see a message indicating insufficient data for analysis.

---

### Edge Cases

- What happens when a run has zero heart rate samples? The detail page MUST display summary stats and pace data without a heart rate chart, showing a "No heart rate data" message.
- What happens when a batch sync request contains both new and duplicate runs? The system MUST process each run independently — creating new ones and skipping duplicates — and return a summary of what was created vs. skipped.
- What happens when the AI provider is unavailable? The system MUST display a user-friendly error message ("Analysis temporarily unavailable, please try again later") and log the error.
- What happens when a user has runs but none in the requested analysis period? The relevant AI skill MUST return a message indicating no data is available for the selected time range.
- What happens when the QR code payload is very long? The QR code generation MUST handle payloads up to 2KB (sufficient for token + base URL).
- What happens when a user deletes a run and then the Android app re-syncs it? The system treats the re-synced run as new (since the original was deleted) and creates it again.

## Clarifications

### Session 2026-02-22

- Q: Should duplicate runs be skipped entirely or upserted (updated)? → A: Skip only — duplicates are ignored, return 200 with "already synced."
- Q: How are heart rate zones defined for HR Zone Analysis? → A: Estimated from observed data — use the user's highest recorded HR as proxy for max HR, standard 5-zone model, no user configuration needed.
- Q: Can runs be deleted, and if so from where? → A: Delete from web dashboard only — a delete button on the run detail page with confirmation. No API delete endpoint.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST accept run data (distance, duration, pace, steps, heart rate, heart rate samples, pace splits) via a REST API and persist it.
- **FR-002**: System MUST prevent duplicate runs by matching on `start_time` + `distance_km` per user. Duplicates are skipped entirely (not updated).
- **FR-002a**: System MUST allow users to delete runs from the web dashboard (run detail page). Deletion is permanent and cascades to all associated heart rate samples and pace splits.
- **FR-003**: System MUST support batch ingestion of multiple runs in a single API request.
- **FR-004**: System MUST authenticate API requests using Bearer tokens.
- **FR-005**: System MUST display a paginated, sortable, filterable run diary after web login.
- **FR-006**: System MUST display detailed run information including heart rate and pace charts.
- **FR-007**: System MUST allow users to generate named API tokens and display them as QR codes.
- **FR-008**: System MUST allow users to revoke API tokens.
- **FR-009**: System MUST provide 6 AI analysis skills that return structured, renderable output.
- **FR-010**: System MUST scope all data access to the authenticated user (no cross-user data leaks).
- **FR-011**: System MUST version the API under `/api/v1/` to allow future breaking changes under `/api/v2/`.
- **FR-012**: System MUST use the existing web authentication (email/password) provided by the starter kit.

### Key Entities

- **User**: A runner who uses both the Android app and the web dashboard. Has many Runs and many API tokens. Authenticated via email/password on web, via Sanctum tokens on API.
- **Run**: A single running session. Key attributes: start_time, end_time, distance_km, duration_seconds, steps, avg_heart_rate, avg_pace_seconds_per_km. Belongs to a User. Has many HeartRateSamples and PaceSplits.
- **HeartRateSample**: A single heart rate reading during a run. Key attributes: timestamp, bpm. Belongs to a Run.
- **PaceSplit**: A per-kilometer pace breakdown. Key attributes: kilometer_number, split_time_seconds, pace_seconds_per_km, is_partial, partial_distance_km. Belongs to a Run.
- **PersonalAccessToken**: A Sanctum API token with a descriptive name. Belongs to a User. Used by the Android app to authenticate API calls.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A run synced from the Android app is visible on the web dashboard within 5 seconds of the API response.
- **SC-002**: Duplicate run detection correctly identifies and skips 100% of re-synced runs (matched by start_time + distance_km).
- **SC-003**: The run diary loads and displays the first page of runs within 2 seconds for a user with up to 1,000 runs.
- **SC-004**: All 6 AI analysis skills return structured results within 30 seconds (accounting for external AI provider latency).
- **SC-005**: A newly generated API token, when scanned via QR code on the Android app, successfully authenticates on the first attempt.
- **SC-006**: Users can generate, view, and revoke API tokens entirely from the web dashboard without technical knowledge.
- **SC-007**: The run detail page renders all charts and the splits table correctly for runs with 1 to 100+ kilometer splits.

### Assumptions

- The Android app handles GPS tracking, heart rate monitoring, and local storage. This spec covers only the server-side and web dashboard.
- The user has a modern browser (Chrome, Firefox, Safari, Edge — latest 2 versions).
- The AI provider (Anthropic Claude) has reasonable uptime and the `laravel/ai` package is stable enough for structured output by the time AI features are implemented.
- Heart rate samples can range from dozens (short run) to thousands (marathon with per-second sampling). The system must handle this range without performance issues on the detail page.
- The Android app sends all run data in metric units (km, seconds, bpm).
