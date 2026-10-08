# PathYatra frontend

React/Vite contains both traveler and admin interfaces. Follow the [root setup guide](../README.md), preferably using Docker Compose for the whole stack.

Development runs at http://localhost:5180 and proxies `/api` to PHP at http://127.0.0.1:8001 (override with `PATHYATRA_PHP_URL`). The PHP development command uses `router.php` to expose only API endpoints. Vite fails if port 5180 is occupied, avoiding accidental use of an older project. The production Nginx server supports direct links to React routes.

The API client bootstraps the session's CSRF token, sends it on writes, and handles session expiration. Both generated and saved plans use the same view/PDF shape. Admin catalog management uses the same REST API; there are no separate PHP admin screens.

Map lines show approximate destination sequence rather than road directions. Location is optional, permission-based and subject to HTTPS browser requirements. Illustrations under `public/images` are generated scenery inspired by Nepal, not verified photographs of specific landmarks. Visual styling is retained in this functionality phase.
