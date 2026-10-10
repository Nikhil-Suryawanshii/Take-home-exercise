# Staff room view

React and TypeScript. The nav bar opens one table at a time:

| Table | What it is | What you can do |
| --- | --- | --- |
| Nursery groups | `tenants` | Add, rename, delete, then choose one |
| Rooms | `rooms` for that group | Add, rename, delete, then open one |
| Children | `children` for that group | Add, rename, delete |
| Who is here | open rows in `check_ins` | Check a child in by name, or check them out |

`check_ins` is not a free-form edit table. A stay is opened by check-in and closed by check-out.

A second check-in stays a `409` from the backend. This screen shows that message and does not move the child. A room or child that still has check-ins cannot be deleted.

## Run

Start the Laravel API first, from `backend/`:

```bash
php artisan serve
```

Then, from this folder:

```bash
npm install
npm run dev
```

Vite proxies `/api` to `http://127.0.0.1:8000`.

## Tests

```bash
npm test
```
