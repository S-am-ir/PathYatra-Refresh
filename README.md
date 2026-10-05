# PathYatra

PathYatra is a Nepal itinerary planning prototype. The React interface is in `frontend/`; PHP endpoints are in `api/`; MySQL schema and initial catalog are in `database/`. This refreshed version is based on the original [Dokr101/PathYatra](https://github.com/Dokr101/PathYatra) project.

The planner filters activities by a month-based season, ranks activity categories against traveler interests, orders selected destinations with a Haversine nearest-neighbor heuristic, and fills morning, afternoon, and evening slots within an estimated activity allowance. The route lines are straight connections between destination coordinates, not road directions. Accommodation uses price tiers rather than live hotel inventory.

## Run locally

Requirements: PHP 8+ with `pdo_mysql`, MySQL 8 or MariaDB 10+, Node.js and npm. Run the PHP server and Vite on the same computer; the frontend proxies its API calls to PHP.

1. Clone this repository. Import `database/schema.sql` and then `database/seed.sql` into MySQL, for example with `mysql -u root -p < database/schema.sql` followed by `mysql -u root -p < database/seed.sql`. The schema creates the `yatra_db` database. The seed contains a small sample catalog and demo accounts.
2. From the repository root, start PHP in one terminal: `php -S 127.0.0.1:8001 -t .`. Database defaults are `127.0.0.1`, `yatra_db`, `root`, and an empty password. Set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the PHP server's environment if yours differ.
3. From `frontend/`, run `npm ci`, then `npm run dev`. The API proxy and development admin links default to `http://127.0.0.1:8001`.
4. Open **`http://localhost:5180`** for the refreshed React interface. Browse destinations, create a plan, register an account, then save and reopen the plan. The PHP server at port 8001 serves the API and separate legacy pages; opening its root URL shows the older PHP homepage.

Vite uses port 5180 strictly: if it is occupied, startup fails with a clear error instead of moving to another port. Stop any existing process on that port and start Vite from this repository's `frontend/` directory. If you previously set `PATHYATRA_PHP_URL` or `VITE_PHP_BASE_URL`, remove the old values or change both to `http://127.0.0.1:8001`, then restart Vite.

If using XAMPP instead, put this refreshed project under Apache's document root and point `PATHYATRA_PHP_URL` and `VITE_PHP_BASE_URL` to its actual PHP URL, such as `http://localhost/PathYatra-Refresh`. Continue to open port 5180 for the React interface. `npm run preview` uses port 4180 for inspecting a production build; it does not configure the PHP API proxy.

See [the frontend guide](frontend/README.md) for the interface and deployment notes.

## Current boundaries

The React experience is the primary traveler interface. The repository also includes older PHP-rendered pages, including admin catalog screens. They remain separate from the React styling. The initial catalog contains five destinations and 26 activities; it can grow through database/admin updates. Budget figures exclude transport, meals, live lodging prices, and availability. The illustrated website scenery is not a verified photo of a named location.
