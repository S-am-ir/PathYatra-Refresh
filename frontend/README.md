# PathYatra frontend

React/Vite is the traveler-facing interface. PHP endpoints in the repository's `api/` directory serve catalog, authentication, and itinerary data; MySQL stores the catalog and saved plans.

## Local setup

Follow the repository root [setup guide](../README.md) to import the database, run PHP on `127.0.0.1:8001`, and start Vite. Open **`http://localhost:5180`** for the refreshed interface. The API proxy and development admin links default to port 8001, so `npm run dev` needs no URL overrides for this setup.

For XAMPP or a different PHP server, set `PATHYATRA_PHP_URL` (the Vite API proxy target) and `VITE_PHP_BASE_URL` (the admin links) to that project's actual PHP URL before starting Vite. Replace any old values pointing to the original project. The frontend uses port 5180 strictly; preview uses 4180 strictly.

`npm run build` creates a production bundle in `dist/`. Deploying the bundle still requires routing `/api` to the PHP endpoints and a fallback to the SPA entry page for React routes.

## Current experience

- Visitors can browse the database catalog and generate a planning outline without an account.
- An account is required to save, view, complete, and delete saved trips.
- The itinerary map shows destination sequence with straight connecting lines. It is not a road route.
- Accommodation and activity amounts are estimates. Transport, meals, hotel availability, and current road conditions are not priced or checked.
- The homepage images in `public/images/` are generated illustrative scenery inspired by Nepal; they do not document a particular landmark.

The visual system is in `src/styles/tokens.css`, `src/pages/public/home.css`, and the focused stylesheets beside each page. The PHP server-rendered pages remain a separate legacy interface.
