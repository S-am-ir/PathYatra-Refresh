# Functional verification

Checked in this workspace and on a GitHub-hosted Ubuntu 24.04 runner on 2026-10-08, using PHP 8.3, MariaDB 10.11 and the React/Vite code. The expanded Docker/Chromium audit passed for commit `375dc31a4ac4e5579a07a6813420377b796f255e`: [successful run](https://github.com/S-am-ir/PathYatra-Refresh/actions/runs/37800791013). These results describe this revision, not a guarantee that every possible environment or input is issue-free. See [AUDIT.md](AUDIT.md) for the user journeys, independent algorithm checks and fixes.

| Check | Result |
| --- | --- |
| Scheduler tests | 4,293 assertions: four-stop/origin ordering and ties, interest ranking and tie breaks, deterministic output, per-day season changes, tier boundaries, Haversine reference cases, transfer days, duration bounds, spanning activities, uniqueness, randomized budgets and totals |
| HTTP/API integration | 56 checks: authentication, CSRF, role/ownership restrictions, catalog filters/CRUD, invalid inputs, generation, trusted/idempotent save, reopening, maximum-length destination names on transfer days, account deactivation, completion/reviews, review edit/averages and deletion |
| Generation timing | Local single-request tests completed in 3–19 ms, below the proposal's three-second target; not a load benchmark |
| Upgrade from original schema | Users and an older saved trip preserved, newest duplicate review retained, new catalog inserted, repeat migration successful |
| React forms with real API | Simulated DOM: registration redirects, location success/denial/manual fallback, wizard validation, generation, saved-trip completion/deletion, review editing, admin CRUD/status and combined filters |
| PDF export | A 30-day, seven-page export retained the final day, budget, warnings and footer; extracted characters stayed within page bounds |
| Frontend build | Production Vite build passed |
| PHP syntax | All current PHP files passed syntax checks |
| Database account permissions | Migration succeeded with an account restricted to its application database |
| Compose configuration | Validated with Docker Compose 2.40; startup dependencies and database health check configured |
| Actual Docker build/startup | Passed on the GitHub runner: PHP and web images built, a fresh MariaDB volume initialized, migration exited successfully, API/web readiness passed |
| Actual Chromium desktop | Passed: filters/details, protected registration, supplied browser location, generation, live map tiles/route/zoom, PDF download, save/reload, completion date enforcement, reviews/editing, deletion and admin CRUD/status |
| Phone-sized Chromium | Passed at 390 × 844: browser location denial, manual origin, generation, live map tiles/route/zoom and no horizontal overflow in planner/result |
| Database persistence | Full Compose teardown/recreation without deleting the volume preserved the registered traveler and exact saved days, map data and budget summary |
| Alternate published port | Port 5181 served the API health endpoint and the planner SPA route successfully |
| Browser runtime errors | None during the passing desktop/mobile flows, including zooming and immediately leaving a saved plan |
| Expanded user audit | Five browser journeys passed: public/account pages, errors and recovery, home prefill through registration, nine independently validated plan scenarios, live admin catalog edits, preserved history after catalog deletion and session revocation |
| Screenshot/PDF inspection | Inspected desktop/mobile public, traveler and admin screenshots, maps and the final day of a 30-day plan; rendered the two downloaded PDFs and checked final days, dates, budgets and text bounds |

## Limits of this verification

This workspace cannot run Docker or Chromium itself; those checks were completed on the GitHub runner instead. The browser tests use real Chromium, not a simulated DOM, and downloaded live OpenStreetMap tiles. Desktop geolocation uses supplied test coordinates, and the mobile test verifies browser denial/manual fallback. Physical GPS hardware, a person's interactive permission prompt, Safari/Firefox and other operating systems remain local/device checks. The mobile run is a phone-sized Chromium viewport, not a physical phone. Generation timing is a single-request check, not a load benchmark.

The first actual startup run exposed an IPv6 `localhost` readiness probe against an IPv4 Nginx listener; the probe now uses `127.0.0.1`. Browser testing also exposed a Leaflet 1.9 zoom callback firing after route removal. The map uses immediate zoom transitions and stable route points, and the regression flow zooms then immediately navigates away. No browser errors are ignored to make the tests pass.

Run `docker compose up --build -d` and `docker compose --profile test run --rm test` on a machine with Docker to verify that environment. Manual browser checks should cover registration/sign-in, phone/desktop planning, location success or fallback, map display, saving/reopening and PDF download.

## Real Docker/browser test runner

The `Docker and browser verification` GitHub Actions workflow **passed**. It builds the actual images, waits for Compose readiness, runs the scheduler/API tests inside Docker, and launches Chromium through Playwright. It retains screenshots, downloaded PDFs, generated-plan JSON, failure traces, an HTML report and container logs for inspection. It also checks an alternate published port and recreates the stack without deleting the database volume to compare the reopened plan with the original snapshot. All five browser journeys passed in 1.1 minutes; the API generation sample took 2 ms.

The [passing run's artifact](https://github.com/S-am-ir/PathYatra-Refresh/actions/runs/37800791013/artifacts/11561231241) includes 25 desktop/mobile screenshots, two PDFs, generated-plan evidence and the HTML report. GitHub artifacts expire after 14 days; the workflow can be rerun to create fresh evidence.

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
