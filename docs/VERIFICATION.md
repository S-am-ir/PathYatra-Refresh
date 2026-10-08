# Functional verification

Checked in this workspace on 2026-10-08, using PHP 8.3, MariaDB 10.11 and the React/Vite code. These results describe this revision, not a guarantee that every possible environment or input is issue-free.

| Check | Result |
| --- | --- |
| Scheduler tests | 4,269 assertions: season boundaries, origin ordering, Haversine reference cases, transfer days, duration bounds, spanning activities, uniqueness, randomized budgets and summary totals |
| HTTP/API integration | 52 checks: authentication, CSRF, role/ownership restrictions, catalog filters/CRUD, invalid inputs, generation, trusted/idempotent save, reopening, account deactivation, completion/review eligibility, review edit/averages and deletion |
| Generation timing | Local single-request tests completed in 3–19 ms, below the proposal's three-second target; not a load benchmark |
| Upgrade from original schema | Users and an older saved trip preserved, newest duplicate review retained, new catalog inserted, repeat migration successful |
| React forms with real API | Simulated DOM: registration redirects, location success/denial/manual fallback, wizard validation, generation, saved-trip completion/deletion, review editing, admin CRUD/status and combined filters |
| PDF export | A 30-day, seven-page export retained the final day, budget, warnings and footer; extracted characters stayed within page bounds |
| Frontend build | Production Vite build passed |
| PHP syntax | All current PHP files passed syntax checks |
| Database account permissions | Migration succeeded with an account restricted to its application database |
| Compose configuration | Validated with Docker Compose 2.40; startup dependencies and database health check configured |

## Limits of this verification

A full Chromium session could not start because the workspace lacks the process filesystem it needs. The simulated DOM tests do not verify screen layout, Leaflet rendering, real device geolocation, permission prompts, or browser download behavior. PDF document creation was checked independently. **A Docker engine is unavailable here, so the actual image build and Compose startup remain unverified.** PHP/MariaDB were run together directly, with the same schema, migration and API tests used by the Compose test service.

Run `docker compose up --build -d` and `docker compose --profile test run --rm test` on a machine with Docker to verify that environment. Manual browser checks should cover registration/sign-in, phone/desktop planning, location success or fallback, map display, saving/reopening and PDF download.

## Real Docker/browser test runner

The `Docker and browser verification` GitHub Actions workflow is prepared but **has not run yet**. It builds the actual images, waits for Compose readiness, runs the scheduler/API tests inside Docker, and launches Chromium through Playwright. It retains screenshots, the downloaded PDF, failure traces, an HTML report and container logs for inspection. It also checks an alternate published port and recreates the stack without deleting the database volume to compare the reopened plan with the original snapshot.

The browser tests exercise the actual result page and Leaflet SVG markers/route, require successfully downloaded live OpenStreetMap tiles, and check desktop and phone-sized screens. They cover registration, browser geolocation with supplied test coordinates, manual origin after permission denial, generation, saving/reopening, PDF download, completion/reviews, deletion and admin maintenance. Supplied browser coordinates are not a physical GPS test. Tile requests are not mocked; an external tile-service/network failure will fail the map check and must be inspected separately.

On a machine with Docker and Node 22+, use a **fresh disposable Compose installation** with the demo accounts and no existing reviews. The test helper expires only its own uniquely named test trip to exercise completion without weakening production date rules.

```sh
export COMPOSE_PROJECT_NAME=pathyatra-verification
docker compose up --build -d --wait
docker compose --profile test run --rm test
npm ci --prefix frontend
cd frontend
npx playwright install --with-deps chromium
TEST_ALLOW_WRITES=1 TEST_DOCKER_RESTART=1 npm run test:browser
```

`TEST_DOCKER_RESTART=1` stops and recreates this test stack during the browser test while retaining its database volume. Leave it unset to skip that check. Use `TEST_WEB_URL` and `APP_PORT` together if port 5180 is occupied. The workflow uses the separate Compose project `pathyatra-verification` and removes that disposable volume at the end.

For a native PHP/database setup, the React form tests can be repeated after `npm ci` in `frontend`:

```sh
TEST_ALLOW_WRITES=1 npm run test:ui --prefix frontend
```

They use the API at `http://127.0.0.1:8001` by default and PHP CLI for isolated historical fixtures/cleanup. Set `TEST_BASE_URL`, database environment variables, and optionally `TEST_PHP_BIN` / `TEST_PHP_INI` if needed. Run only on a local/test database. Location is simulated, and the result-page route is a navigation marker rather than a rendered Leaflet map.
