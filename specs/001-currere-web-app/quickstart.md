# Quickstart: Currere Web

**Branch**: `001-currere-web-app` | **Date**: 2026-02-22

## Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 20+
- npm 10+
- SQLite (development) or PostgreSQL/MySQL (production)

## Setup

```bash
# Clone and install
git clone <repo-url> currere-web
cd currere-web
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite for development)
touch database/database.sqlite
php artisan migrate

# Seed (optional — creates test user + sample runs)
php artisan db:seed

# Build frontend
npm run build

# Start development server
composer run dev
```

## New Dependencies (this feature)

```bash
# Backend
composer require laravel/sanctum
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate

# Frontend
npm i layerchart@next
npm i qrcode
npm i -D @types/qrcode
```

## Environment Variables (add to .env)

```env
# AI Provider
ANTHROPIC_API_KEY=sk-ant-...
```

## Verify Installation

```bash
# Run tests
php artisan test --compact

# Check API routes exist
php artisan route:list --path=api/v1

# Check Sanctum is configured
php artisan tinker --execute="echo class_exists('Laravel\Sanctum\Sanctum') ? 'OK' : 'MISSING';"
```

## Key URLs

| URL                        | Description              |
|----------------------------|--------------------------|
| `/dashboard`               | Run diary (after login)  |
| `/runs/{id}`               | Run detail page          |
| `/analysis`                | AI analysis page         |
| `/settings/tokens`         | API token management     |
| `/api/v1/runs`             | REST API — list runs     |

## Testing the API

```bash
# Generate a token (from web dashboard settings/tokens page, or via tinker)
php artisan tinker --execute="\$u = App\Models\User::first(); echo \$u->createToken('test')->plainTextToken;"

# Sync a run
curl -X POST http://localhost:8000/api/v1/runs \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "start_time": "2026-02-22T08:30:00Z",
    "end_time": "2026-02-22T09:15:00Z",
    "distance_km": 10.5,
    "duration_seconds": 2700,
    "steps": 12500,
    "avg_heart_rate": 155,
    "avg_pace_seconds_per_km": 257,
    "heart_rate_samples": [
      {"timestamp": "2026-02-22T08:30:00Z", "bpm": 120}
    ],
    "pace_splits": [
      {"kilometer_number": 1, "split_time_seconds": 260, "pace_seconds_per_km": 260, "is_partial": false}
    ]
  }'

# List runs
curl http://localhost:8000/api/v1/runs \
  -H "Authorization: Bearer <token>"
```
