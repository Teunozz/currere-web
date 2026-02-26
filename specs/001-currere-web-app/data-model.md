# Data Model: Currere Web

**Branch**: `001-currere-web-app` | **Date**: 2026-02-22

## Entity Relationship Diagram

```text
User (1) ──── (*) Run (1) ──── (*) HeartRateSample
  │                │
  │                └──── (*) PaceSplit
  │
  └──── (*) PersonalAccessToken (Sanctum built-in)
```

## Entities

### User (existing — extend)

The starter kit's User model already exists. Add the `HasApiTokens` trait for Sanctum.

| Field                      | Type         | Constraints                     | Notes                    |
|----------------------------|--------------|---------------------------------|--------------------------|
| id                         | bigint       | PK, auto-increment              | Existing                 |
| name                       | string(255)  | required                         | Existing                 |
| email                      | string(255)  | required, unique                 | Existing                 |
| email_verified_at          | datetime     | nullable                         | Existing                 |
| password                   | string(255)  | required, hashed                 | Existing                 |
| two_factor_secret          | text         | nullable, encrypted              | Existing                 |
| two_factor_recovery_codes  | text         | nullable, encrypted              | Existing                 |
| two_factor_confirmed_at    | datetime     | nullable                         | Existing                 |
| remember_token             | string(100)  | nullable                         | Existing                 |
| created_at                 | datetime     |                                  | Existing                 |
| updated_at                 | datetime     |                                  | Existing                 |

**Relationships**: `hasMany(Run)`, `morphMany(PersonalAccessToken)` via Sanctum

### Run (new)

| Field                    | Type           | Constraints                                         | Notes                              |
|--------------------------|----------------|------------------------------------------------------|------------------------------------|
| id                       | bigint         | PK, auto-increment                                   |                                    |
| user_id                  | bigint         | FK → users.id, required, indexed                      |                                    |
| start_time               | datetime       | required                                              | UTC                                |
| end_time                 | datetime       | required                                              | UTC                                |
| distance_km              | decimal(8,3)   | required, min: 0                                      | e.g., 5.123                        |
| duration_seconds         | integer        | required, min: 0                                      |                                    |
| steps                    | integer        | nullable, min: 0                                      | Not all devices track steps        |
| avg_heart_rate           | integer        | nullable, min: 0                                      | BPM, null if no HR sensor          |
| avg_pace_seconds_per_km  | integer        | nullable, min: 0                                      | Derived, null if distance is 0     |
| created_at               | datetime       |                                                       |                                    |
| updated_at               | datetime       |                                                       |                                    |

**Uniqueness**: Composite unique index on `(user_id, start_time, distance_km)` for deduplication.

**Relationships**: `belongsTo(User)`, `hasMany(HeartRateSample)`, `hasMany(PaceSplit)`

**Indexes**: `(user_id, start_time)` for diary sorting/filtering

### HeartRateSample (new)

| Field       | Type      | Constraints                          | Notes                         |
|-------------|-----------|--------------------------------------|-------------------------------|
| id          | bigint    | PK, auto-increment                    |                               |
| run_id      | bigint    | FK → runs.id, required, indexed       | cascade delete                |
| timestamp   | datetime  | required                              | UTC, absolute timestamp       |
| bpm         | integer   | required, min: 0, max: 300            |                               |

**Relationships**: `belongsTo(Run)`

**Indexes**: `(run_id, timestamp)` for ordered retrieval

**Volume**: Dozens to thousands per run (1/sec sampling = ~3,600 for 1hr run)

### PaceSplit (new)

| Field                | Type          | Constraints                          | Notes                         |
|----------------------|---------------|--------------------------------------|-------------------------------|
| id                   | bigint        | PK, auto-increment                    |                               |
| run_id               | bigint        | FK → runs.id, required, indexed       | cascade delete                |
| kilometer_number     | integer       | required, min: 1                      | Sequential km number          |
| split_time_seconds   | integer       | required, min: 0                      | Time for this split           |
| pace_seconds_per_km  | integer       | required, min: 0                      | Pace for this split           |
| is_partial           | boolean       | required, default: false              | True for last incomplete km   |
| partial_distance_km  | decimal(5,3)  | nullable                              | e.g., 0.400 for partial split |

**Relationships**: `belongsTo(Run)`

**Indexes**: `(run_id, kilometer_number)` for ordered retrieval

### PersonalAccessToken (Sanctum built-in)

Managed by Sanctum. No custom migration needed.

| Field         | Type       | Notes                                  |
|---------------|------------|----------------------------------------|
| id            | bigint     | PK                                     |
| tokenable_*   | morphs     | Polymorphic relation to User            |
| name          | string     | Descriptive name (e.g., "Galaxy S25")   |
| token         | string(64) | SHA-256 hash of the plain-text token    |
| abilities     | text       | JSON array (default: `["*"]`)           |
| last_used_at  | datetime   | nullable                                |
| expires_at    | datetime   | nullable                                |
| created_at    | datetime   |                                         |
| updated_at    | datetime   |                                         |

## Validation Rules

### Run Creation (API)

```text
start_time:               required, date, before_or_equal: now
end_time:                 required, date, after: start_time
distance_km:              required, numeric, min: 0.001
duration_seconds:         required, integer, min: 1
steps:                    nullable, integer, min: 0
avg_heart_rate:           nullable, integer, min: 0, max: 300
avg_pace_seconds_per_km:  nullable, integer, min: 0
heart_rate_samples:       nullable, array
heart_rate_samples.*.timestamp: required, date
heart_rate_samples.*.bpm:       required, integer, min: 0, max: 300
pace_splits:              nullable, array
pace_splits.*.kilometer_number:     required, integer, min: 1
pace_splits.*.split_time_seconds:   required, integer, min: 0
pace_splits.*.pace_seconds_per_km:  required, integer, min: 0
pace_splits.*.is_partial:           required, boolean
pace_splits.*.partial_distance_km:  nullable, numeric, min: 0.001, required_if: is_partial,true
```

### Token Creation (Web)

```text
name: required, string, max: 255
```
