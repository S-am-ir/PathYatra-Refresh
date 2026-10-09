# PathYatra

A Nepal trip planner built with React/Vite, PHP REST endpoints and MariaDB. Based on [Dokr101/PathYatra](https://github.com/Dokr101/PathYatra), this version completes the traveler and admin flows described in the proposal.

**Use the `ci/docker-browser-check` branch for the completed version.** The repository's default `main` branch still contains the older revision. [Open the completed branch](https://github.com/S-am-ir/PathYatra-Refresh/tree/ci/docker-browser-check) or [view its commit history](https://github.com/S-am-ir/PathYatra-Refresh/commits/ci/docker-browser-check/).

**[Complete Windows/Linux setup guide](docs/SETUP.md)** - official Docker/Git download links, WSL setup, Linux installation, ZIP or Git checkout, startup, accounts, port changes, updates, persistence, tests and troubleshooting.

## Run with Docker

Install Docker Desktop (or Docker Engine with Compose). No separate PHP, MySQL or Node installation is needed.

The verified functional revision is currently on `ci/docker-browser-check`; `main` has not been merged yet. Clone that branch to test the completed system:

```sh
git clone --branch ci/docker-browser-check https://github.com/S-am-ir/PathYatra-Refresh.git
cd PathYatra-Refresh
docker compose up --build -d
```

Open **http://localhost:5180**. Initial image downloads and building can take a few minutes. `docker compose ps` shows readiness; `docker compose logs init api web` shows startup errors. The database starts first, the migration runs next, and the app starts after that. The API and database ports are internal to Docker; the browser uses one origin.

Demo accounts (created only if their email does not already exist):

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@yatra.com | Admin@123 |
| Traveler | traveler@yatra.com | Traveler@123 |

You can also register a traveler. These accounts are for local demonstration; change their credentials and database passwords before public hosting.

To change the port, copy `.env.example` to `.env`, set `APP_PORT` (for example `5181`), then run `docker compose up --build -d` again. Open that port, including from another device using the host's LAN IP. The selected port and existing database survive app rebuilds. `docker compose down` stops the stack and preserves data. `docker compose down -v` **deletes the database**.

Current location is requested by the planner's **Use my current location** button. Browsers require permission and HTTPS, except on localhost. A phone opening a plain HTTP LAN address may reject location; choose a starting place instead, or use HTTPS. Location sets the initial nearest stop, and is stored with a plan only if you save that plan. Travel to the first stop is not scheduled.

## Working flows

- Register/sign in; role-protected admin access; deactivated accounts lose access.
- Browse/search destinations; region, season, daily estimate and activity category filters; activities and reviews per destination.
- Four-step trip planning with optional current/manual origin, dates, budget and interests.
- Save, reopen, download PDF, complete on or after the final trip date, and delete owned trips.
- Review a destination from a completed trip; update your own review; averages reflect actual reviews.
- Admin destination/activity CRUD, traveler status and analytics from the live database.
- Fresh seed: 25 destinations, 104 activities; editable records and no fabricated reviews.

## Algorithms and limits

The proposal's algorithms remain: month-to-season classification, season filtering, category interest scoring (+10 for a match), budget allocation and accommodation tiers, Haversine distance with nearest-neighbor destination ordering, and greedy morning/afternoon/evening scheduling. The first selected destination starts the route unless an origin is supplied. Ties are resolved deterministically by cost and activity ID.

Scheduling now respects slot capacities (4 / 4 / 3 hours), spans longer activities over morning and afternoon, never repeats an activity, and stays within the daily and total accommodation/activity allowances. Season filtering uses each day's date, including trips across season boundaries. Unfilled slots remain free time.

Intercity transfers use an explicit scheduling heuristic: straight-line distance × 1.5 ÷ 35 km/h, rounded up to a quarter hour. Legs over four hours reserve whole transfer days of up to eight hours each. Shorter transfers occupy the morning. Insufficient trip length returns an explanation instead of an impossible sightseeing plan. **This is not a road route or a live journey-time estimate.** The map's lines are straight connections. Arrival travel, transport fares, meals, permits, live hotels, road conditions and booking availability are outside the model; unallocated budget remains visible for those expenses. See [catalog notes](docs/CATALOG.md).

The browser cannot set saved prices or activities: saving uses a server-generated token and snapshot, valid for 12 hours within the signed-in session (up to ten recent candidates). Duplicate saves return the same trip. Older saved plans are supported by a compatibility reader. Catalog deletion preserves saved snapshots but removes the deleted destination's review eligibility.

## Verification

Against a local/test installation:

```sh
docker compose --profile test run --rm test
```

See [verification results and limits](docs/VERIFICATION.md).

The `Docker and browser verification` GitHub Actions workflow passed actual Docker startup, 4,293 planner assertions, 56 API checks and five Chromium desktop/mobile journeys, including live maps, PDF downloads, catalog changes feeding new plans, saved history surviving catalog deletion, data surviving container recreation and an alternate published port. [View the passing run](https://github.com/S-am-ir/PathYatra-Refresh/actions/runs/37800791013). Its reports include screenshots, generated-plan evidence and container logs. See [the functional audit](docs/AUDIT.md) for findings and algorithm limits, and the verification document for equivalent local commands and remaining device checks.

This runs scheduler invariant tests and HTTP/database flow tests. Integration tests create uniquely named temporary travelers/catalog records and remove them afterward. Use a disposable or local database, not a live deployment.

## Run without Docker

Requirements: PHP 8.3+ with `pdo_mysql` and `mbstring`, MariaDB 10.11+ or MySQL 8+, Node 22+.

Create `yatra_db` with UTF-8, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` as needed, then:

```sh
php database/migrate.php
php -S 127.0.0.1:8001 router.php
```

In another terminal:

```sh
cd frontend
npm ci
npm run dev
```

Open http://localhost:5180. `PATHYATRA_PHP_URL` can change the Vite proxy target. `npm run build` builds the frontend; production serving needs the SPA fallback and `/api` proxy provided by the Docker Nginx configuration. PHP's port is an API service, not the interface. The old duplicate PHP pages have been removed.

For an existing original database, **back it up first**, then run `php database/migrate.php` using its connection settings. The migration adds snapshot/token fields and review uniqueness, preserves users/trips/catalog edits, and keeps the newest duplicate review per author/destination. The migration is repeatable. Importing only `seed.sql` manually does not record its migration version; prefer the migration command.
