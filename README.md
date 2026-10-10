# Nursery Room Management System

Laravel 12 (PHP 8.2) and MySQL. Staff check a child into a room and check them out. A manager can ask who is in a room right now.

Nursery groups are tenants. Every API request sends `X-Tenant-ID`. One tenant cannot read or change another tenant’s data. Authentication is assumed to be done already. The header is the only tenant input.

## Business rule

A child cannot be in two rooms at once. If they already have an open stay, a new check-in is **rejected with 409**. The child is not moved.

Staff check them out of the first room, then check them into the next one. The occupants list then means “in this room right now,” and a move is an explicit action rather than a side effect of a second check-in.

An open stay is a `check_ins` row with `checked_out_at` null.

## Endpoints

Laravel prefixes these with `/api`.

| Method and path | Body | Success |
| --- | --- | --- |
| `POST /api/rooms/{room}/check-ins` | `{ "child_id": 1 }` | `201` |
| `POST /api/check-ins/{id}/check-out` | none | `200` |
| `GET /api/rooms/{room}/occupants` | none | `200` |

Send header `X-Tenant-ID: {tenant id}` and `Accept: application/json`.

### Status codes

| Situation | Code |
| --- | --- |
| Missing or unknown `X-Tenant-ID` | `401` |
| Room, child, or check-in is missing or belongs to another tenant | `404` |
| Body is missing `child_id` | `422` |
| Child already has an open stay | `409` |
| Check-out of a stay that is already closed | `409` |
| Empty room | `200` and `[]` |

A cross-tenant id is `404`, not `403`, so the response does not reveal that the row exists.

Occupant items are `check_in_id`, `child_id`, and `name`. A check-in response includes `id`, `room_id`, `child_id`, and `checked_in_at`. A check-out response adds `checked_out_at`.

## Requirements

- PHP 8.2
- Composer
- MySQL

## Setup

Create the app database and a separate one for tests. PHPUnit wipes `nursery_testing`.

```sql
CREATE DATABASE nursery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nursery_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

From `backend/`:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

`.env.example` already points at MySQL database `nursery` on `127.0.0.1:3306` as user `root` with an empty password. Change `DB_USERNAME` and `DB_PASSWORD` if yours differ. That user must be able to create and drop tables in both databases.

`phpunit.xml` sets `DB_DATABASE` to `nursery_testing`, with the same host and user. Change the test username and password there too if you change `.env`.

```bash
php artisan migrate
php artisan serve
```

The API is at `http://127.0.0.1:8000`.

## Staff screen

The React app is in `frontend/`. A nav bar opens nursery groups, rooms, children, and who is here as separate screens. Names can be added, renamed, and deleted. Open a room to see who is there and check a child in by name. Vite proxies `/api` to `http://127.0.0.1:8000`, so start the Laravel server first.

```bash
cd frontend
npm install
npm run dev
```

Open the URL Vite prints. Tests:

```bash
npm test
```

```bash
curl -i -X POST http://127.0.0.1:8000/api/rooms/1/check-ins \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"child_id": 1}'
```

Rooms, children, and tenants must already exist for that call. A missing room or child returns `404`.

## Tests

From `backend/`:

```bash
php artisan test
```

Tests use `RefreshDatabase` against `nursery_testing`. They do not wipe `nursery`. They call the HTTP routes.

## Design

Controllers stay thin. `CheckInChild`, `CheckOutChild`, and `ListRoomOccupants` hold the rules. `ResolveTenant` reads `X-Tenant-ID` once and stores the tenant on the request. `CurrentTenant` is what controllers use, so they do not read the header.

`Room`, `Child`, and `CheckIn` share a `forTenant` scope. Each of those tables stores `tenant_id` on the row. Queries do not discover the tenant by joining through a parent.

```
backend/
  app/Models/Tenant.php
  app/Models/Room.php
  app/Models/Child.php
  app/Models/CheckIn.php
  app/Http/Middleware/ResolveTenant.php
  app/Actions/CheckInChild.php
  app/Actions/CheckOutChild.php
  app/Actions/ListRoomOccupants.php
  app/Http/Controllers/CheckInController.php
  app/Http/Controllers/OccupantController.php
  routes/api.php
  tests/Feature/CheckInTest.php
  tests/Feature/CheckOutTest.php
  tests/Feature/OccupantsTest.php
  tests/Feature/TenantIsolationTest.php
```

## With more time

Add a MySQL constraint so only one open stay can exist per child, for example a generated column that is the child id while `checked_out_at` is null and a unique key on that column. This pass enforces the rule in `CheckInChild`, with a row lock, and covers it in tests. The database rule was left out so the behaviour stayed in one place that the tests already describe.

## Left out on purpose

- Authentication
- A `nurseries` table. The tenant is the boundary. The API never receives a nursery id.
- Editing a check-in row by hand. Stays are opened and closed through check-in and check-out.

## AI tools

AI was used to turn the brief into the phase notes in `mdFiles/`, to implement the Laravel API (models, tenant middleware, actions, controllers, and feature tests), and to draft this README.

Rejected or changed from that output:

- SQLite was rejected. This project uses MySQL.
- Laravel 13 was rejected because this machine is PHP 8.2. The app is Laravel 12.
- Automatically moving a child into a second room was rejected. A second check-in returns `409`.
- Cross-tenant access returns `404`, not `403`.
- Tests call the routes. They do not call the actions directly.
