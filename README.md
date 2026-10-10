# Nursery Room Management API

## 1. Project Overview

This project is a REST API for managing children in nursery rooms.

Staff can:

- Check children into rooms.
- Check children out of rooms.
- View children currently present in a room.

The application supports multiple tenants and ensures that one tenant cannot access or modify another tenant's data.

## 2. Technology Stack

- PHP 8.2
- Laravel 12
- MySQL
- PHPUnit
- REST API with JSON responses

A small React and TypeScript staff screen lives in `frontend/`. The API is the main submission.

## 3. Features

- Child check-in and checkout.
- Current room occupant listing.
- Tenant-based data isolation using the `X-Tenant-ID` request header.
- Request validation and error handling.
- Prevention of multiple active check-ins for the same child.
- Automated feature tests.

## 4. API Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/rooms/{room}/check-ins` | Check a child into a room |
| POST | `/api/check-ins/{id}/check-out` | Check a child out |
| GET | `/api/rooms/{room}/occupants` | List current occupants |

Send `X-Tenant-ID` and `Accept: application/json` on every request under the tenant middleware.

### Check-in example

Request header:

`X-Tenant-ID: 1`

Request body:

```json
{
  "child_id": 1
}
```

```bash
curl -i -X POST http://127.0.0.1:8000/api/rooms/1/check-ins \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"child_id": 1}'
```

A successful check-in returns `201` with `id`, `room_id`, `child_id`, and `checked_in_at`. The room and child must already belong to that tenant.

### Check-out example

`POST /api/check-ins/15/check-out`

Include the appropriate `X-Tenant-ID` header. A successful check-out returns `200` and adds `checked_out_at`.

### List occupants example

`GET /api/rooms/1/occupants`

Include the appropriate `X-Tenant-ID` header. Each occupant is `check_in_id`, `child_id`, and `name`. An empty room returns `200` and `[]`.

### Status codes

| Situation | Code |
| --- | --- |
| Missing or unknown `X-Tenant-ID` | `401` |
| Room, child, or check-in is missing or belongs to another tenant | `404` |
| Body is missing `child_id` | `422` |
| Child already has an open stay | `409` |
| Check-out of a stay that is already closed | `409` |
| Empty room | `200` and `[]` |

A cross-tenant id is `404`, so the response does not reveal that the row exists.

## 5. Business Decision

### Preventing multiple active check-ins

A child cannot be checked into a second room while an existing check-in remains active.

The API rejects the new check-in with `409` and requires staff to check the child out of the existing room first. The child is not moved automatically.

This approach prevents accidental room changes and maintains consistent records of each child's current location.

An open stay is a `check_ins` row with `checked_out_at` null.

## 6. Tenant Isolation

Every relevant database query is scoped to the current tenant.

The `X-Tenant-ID` header identifies the tenant for the exercise. `ResolveTenant` reads it once and stores the tenant on the request. `Room`, `Child`, and `CheckIn` use a `forTenant` scope, and each of those tables stores `tenant_id` on the row.

Requests attempting to read or modify another tenant's records are rejected with `404`.

Authentication is assumed to be handled externally, as specified in the assignment.

## 7. Installation and Setup

### Prerequisites

- PHP 8.2
- Composer
- MySQL

The Laravel application is in `backend/`.

### Installation

Clone the repository:

```bash
git clone YOUR_REPOSITORY_URL
cd YOUR_PROJECT_DIRECTORY
```

Create the app database and a separate one for tests. PHPUnit uses `nursery_testing` and does not wipe `nursery`.

```sql
CREATE DATABASE nursery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nursery_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Install dependencies from `backend/`:

```bash
cd backend
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

`.env.example` already points at MySQL. Set your own user and password if they differ:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nursery
DB_USERNAME=root
DB_PASSWORD=
```

`phpunit.xml` sets `DB_DATABASE` to `nursery_testing` with the same host and user. Change the test username and password there too if you change `.env`.

Run the migrations:

```bash
php artisan migrate
```

Start the Laravel development server:

```bash
php artisan serve
```

The default local URL is `http://127.0.0.1:8000`.

### Staff screen

From `frontend/`, after the API is running:

```bash
cd frontend
npm install
npm run dev
```

Vite proxies `/api` to `http://127.0.0.1:8000`. The nav bar opens nursery groups, rooms, children, and who is here as separate screens.

## 8. Running Tests

From `backend/`:

```bash
php artisan test
```

Tests call the HTTP routes against `nursery_testing`. They cover:

- Successful check-in and checkout.
- Current occupant listing.
- Invalid room and child IDs.
- Duplicate active check-ins.
- Already checked-out records.
- Tenant isolation for reading and modifying data.

Frontend tests, from `frontend/`:

```bash
npm test
```

## 9. Future Improvements

With more time, I would:

- Add a MySQL unique rule so only one open stay can exist per child.
- Add further concurrency and edge-case tests.
- Improve API documentation.
- Expand integration tests and monitoring.

The open-stay rule is enforced in `CheckInChild` with a row lock, and covered by tests.

## 10. Intentionally Left Out

- Login and registration, because authentication is assumed to be handled.
- A `nurseries` table. The tenant is the boundary. The API never receives a nursery id.
- Editing a check-in row by hand. Stays are opened and closed through check-in and check-out.

## 11. AI Tool Usage

I used an AI assistant for these improvements:

- Controllers: tighter validation and clearer status codes.
- Check-in: a child with an open stay cannot be checked into another room. That request returns `409`.
- Check-out: checking out a stay that is already closed returns `409`.
