# API Contract: Currere Web v1

**Base URL**: `/api/v1`
**Authentication**: Bearer token (Sanctum)
**Content-Type**: `application/json`

All endpoints require `Authorization: Bearer {token}` header. All responses return JSON.

---

## POST /api/v1/runs

Create a single run with associated heart rate samples and pace splits.

**Request Body**:
```json
{
  "start_time": "2026-02-22T08:30:00Z",
  "end_time": "2026-02-22T09:15:00Z",
  "distance_km": 10.5,
  "duration_seconds": 2700,
  "steps": 12500,
  "avg_heart_rate": 155,
  "avg_pace_seconds_per_km": 257,
  "heart_rate_samples": [
    { "timestamp": "2026-02-22T08:30:00Z", "bpm": 120 },
    { "timestamp": "2026-02-22T08:30:05Z", "bpm": 125 }
  ],
  "pace_splits": [
    { "kilometer_number": 1, "split_time_seconds": 260, "pace_seconds_per_km": 260, "is_partial": false, "partial_distance_km": null },
    { "kilometer_number": 11, "split_time_seconds": 130, "pace_seconds_per_km": 260, "is_partial": true, "partial_distance_km": 0.5 }
  ]
}
```

**Response 201 Created** (new run):
```json
{
  "data": {
    "id": 42,
    "start_time": "2026-02-22T08:30:00Z",
    "end_time": "2026-02-22T09:15:00Z",
    "distance_km": 10.5,
    "duration_seconds": 2700,
    "steps": 12500,
    "avg_heart_rate": 155,
    "avg_pace_seconds_per_km": 257,
    "created_at": "2026-02-22T09:20:00Z"
  }
}
```

**Response 200 OK** (duplicate skipped):
```json
{
  "data": {
    "id": 42,
    "start_time": "2026-02-22T08:30:00Z",
    "distance_km": 10.5,
    "already_synced": true
  }
}
```

**Response 422 Unprocessable Entity** (validation failure):
```json
{
  "message": "The start time field is required.",
  "errors": {
    "start_time": ["The start time field is required."]
  }
}
```

---

## POST /api/v1/runs/batch

Batch sync multiple runs. Each run is processed independently.

**Request Body**:
```json
{
  "runs": [
    { "start_time": "...", "end_time": "...", "distance_km": 5.0, "duration_seconds": 1500, "...": "..." },
    { "start_time": "...", "end_time": "...", "distance_km": 10.0, "duration_seconds": 3000, "...": "..." }
  ]
}
```

**Response 201 Created**:
```json
{
  "data": {
    "created": 1,
    "skipped": 1,
    "results": [
      { "index": 0, "status": "created", "id": 43 },
      { "index": 1, "status": "skipped", "id": 42, "already_synced": true }
    ]
  }
}
```

**Response 422 Unprocessable Entity** (validation failure on batch or individual runs):
```json
{
  "message": "The runs.0.start_time field is required.",
  "errors": {
    "runs.0.start_time": ["The runs.0.start_time field is required."]
  }
}
```

---

## GET /api/v1/runs

List runs for the authenticated user, paginated.

**Query Parameters**:
| Parameter  | Type    | Default | Description            |
|------------|---------|---------|------------------------|
| page       | integer | 1       | Page number            |
| per_page   | integer | 15      | Items per page (max 100) |

**Response 200 OK**:
```json
{
  "data": [
    {
      "id": 42,
      "start_time": "2026-02-22T08:30:00Z",
      "end_time": "2026-02-22T09:15:00Z",
      "distance_km": 10.5,
      "duration_seconds": 2700,
      "steps": 12500,
      "avg_heart_rate": 155,
      "avg_pace_seconds_per_km": 257,
      "created_at": "2026-02-22T09:20:00Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
}
```

---

## GET /api/v1/runs/{id}

Get full run detail including heart rate samples and pace splits.

**Response 200 OK**:
```json
{
  "data": {
    "id": 42,
    "start_time": "2026-02-22T08:30:00Z",
    "end_time": "2026-02-22T09:15:00Z",
    "distance_km": 10.5,
    "duration_seconds": 2700,
    "steps": 12500,
    "avg_heart_rate": 155,
    "avg_pace_seconds_per_km": 257,
    "created_at": "2026-02-22T09:20:00Z",
    "heart_rate_samples": [
      { "timestamp": "2026-02-22T08:30:00Z", "bpm": 120 },
      { "timestamp": "2026-02-22T08:30:05Z", "bpm": 125 }
    ],
    "pace_splits": [
      { "kilometer_number": 1, "split_time_seconds": 260, "pace_seconds_per_km": 260, "is_partial": false, "partial_distance_km": null },
      { "kilometer_number": 11, "split_time_seconds": 130, "pace_seconds_per_km": 260, "is_partial": true, "partial_distance_km": 0.5 }
    ]
  }
}
```

**Response 404 Not Found** (run doesn't exist or belongs to another user):
```json
{
  "message": "Not found."
}
```

---

## Error Responses (all endpoints)

**401 Unauthorized** (missing or invalid token):
```json
{
  "message": "Unauthenticated."
}
```

---

## Web Dashboard Endpoints (Inertia)

These are standard Inertia routes, not API endpoints. Listed for completeness.

| Method | Path                  | Page Component        | Description                |
|--------|-----------------------|-----------------------|----------------------------|
| GET    | /dashboard            | runs/Index.svelte     | Run diary (home)           |
| GET    | /runs/{run}           | runs/Show.svelte      | Run detail                 |
| DELETE | /runs/{run}           | —                     | Delete run (redirect back) |
| GET    | /analysis             | analysis/Index.svelte | AI analysis page           |
| POST   | /analysis/{skill}     | —                     | Run AI skill (returns JSON)|
| GET    | /settings/tokens      | settings/Tokens.svelte| API token management       |
| POST   | /settings/tokens      | —                     | Generate new token         |
| DELETE | /settings/tokens/{id} | —                     | Revoke token               |
