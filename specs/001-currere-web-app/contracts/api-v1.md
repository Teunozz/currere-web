# API Contract: Currere Web v1

**Base URL**: `/api/v1`
**Authentication**: Bearer token (Sanctum)
**Content-Type**: `application/json`

All endpoints require `Authorization: Bearer {token}` header. All responses return JSON.

---

## GET /api/v1/ping

Lightweight health check endpoint to verify API connectivity and token validity.

**Response 200 OK**:
```json
{
  "status": "ok"
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
