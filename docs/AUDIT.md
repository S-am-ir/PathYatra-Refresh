# Functional audit - 2026-10-08

The implemented planner and core public, traveler and administrator journeys passed the expanded audit. No failing check remains in the executed suite. This is evidence for the tested revision and model, not a claim that every input, browser, device or real-world trip has been certified.

Verified application revision: `375dc31a4ac4e5579a07a6813420377b796f255e` on `ci/docker-browser-check`. [Final Docker/browser run](https://github.com/S-am-ir/PathYatra-Refresh/actions/runs/37800791013) passed; [test evidence](https://github.com/S-am-ir/PathYatra-Refresh/actions/runs/37800791013/artifacts/11561231241) contains screenshots, PDFs, plan JSON and reports. The audit changes are on the test branch; main was not merged or modified.

## What was exercised as a user

Real Chromium interacted with the app served by the actual PHP/web/MariaDB containers. The five browser tests contain many actions and checks, rather than five individual clicks.

| Area | Exercised behavior |
| --- | --- |
| Public pages | Home, catalog, combined filters, empty search, destination activities/reviews, login, registration and missing-page handling |
| Accounts | Wrong password, mismatched registration passwords, protected redirects, home selections retained through registration, logout, traveler denial of admin pages |
| Planning | Browser location using supplied coordinates, denied location with manual fallback, origin-based ordering, no-origin ordering, wizard validation/backtracking, insufficient transfer days and successful correction |
| Boundary scenarios | Minimum budget, both sides of accommodation thresholds, a trip crossing November/December, 30 days at the minimum allowance with the whole daily budget consumed by the stay, a four-stop itinerary and a corrected short route |
| Results | Actual day cards, budget totals, live OpenStreetMap tiles, SVG route/markers, initial marker bounds, zoom in/out, immediate navigation after zoom and downloaded PDFs |
| Saved trips | Save, reload, reopen, dashboard counts, list PDF download, cancel deletion, confirm deletion and graceful handling of a deleted trip |
| Completion/reviews | Future-date rejection, completion of an isolated historical test trip, review eligibility, review creation/editing and recomputed average |
| Administration | Destination/activity creation, edits and deletion, live metrics/charts, traveler status, revocation of an already-open traveler session and rejected subsequent login |
| Catalog/history relationship | Changing an activity from NPR 500 to 700 changed a newly generated plan from NPR 2,000 to 2,200; the older saved plan retained its original days and price after the edit and subsequent destination/activity deletion |
| Recovery/persistence | An injected catalog request failure disabled continuing; reload restored the catalog. Full container teardown/recreation retained the traveler and exact saved plan. Port 5181 also served the API and SPA |
| Screens | Public, account, planner and result pages at desktop and 390 x 844; mobile menu open/close, Escape, navigation and page overflow checks |

Screenshots were visually inspected, including rendered charts, route maps, long-page footers and day 30. Two browser-downloaded PDFs were rendered and checked for complete final days, dates, totals, page numbering and text within page bounds. No uncaught JavaScript exceptions occurred in the passing journeys.

## Algorithms checked against outcomes

The PHP suite passed **4,293 assertions**. In addition to randomized invariants, hand-calculated fixtures check four-stop routes, equal-distance ties, changing interests, cost/ID tie breaks, repeatability, date/season boundaries, spanning activities and accommodation thresholds.

Nine browser-generated plans were independently checked against the live activity catalog. A JavaScript spherical-cosine calculation independently checked the distance/nearest-stop results, rather than calling the PHP Haversine code. Calendar dates, per-day seasons, activity ownership, season eligibility, durations, uniqueness, currency totals and daily/trip budget limits were also checked.

| Implemented rule | Meaning and verified limit |
| --- | --- |
| Haversine | Computes straight-line great-circle distances between coordinates; it does not calculate road distance |
| Nearest neighbor | Starts with the nearest selected stop to the origin, or the first selection when no origin exists; repeatedly chooses the closest unvisited stop. Ties preserve selection order. It does not guarantee the globally shortest tour |
| Interest scoring | Matching categories receive priority, with lower cost then lower ID breaking ties; preferred time slots are attempted first |
| Greedy scheduling | Chooses an eligible, unused activity that fits the remaining daily allowance and time block. Morning/afternoon have four hours; evening has three. An activity over four hours can span morning and afternoon, charged once. This is not an exhaustive optimal-combination solver |
| Season filtering | Uses each day's calendar season, including a season change during the trip; unsuitable activities are excluded and destination season warnings are retained |
| Budget/tier rules | Uses integer paisa, a floored daily allowance and database accommodation tiers. Stay plus chosen activities stays within that allowance. Meals, transport and permits are excluded |
| Transfer allowance | Uses straight-line distance x 1.5 / 35 km/h, rounded up to a quarter hour. Legs over four hours reserve full transfer days. Insufficient trip length is rejected with an actionable message |

## Defects found and fixed in this audit

1. **Saving a transfer to a maximum-length destination name failed.** Admin accepted a 150-character name, but the generated label added "Transit to ", exceeding the old 150-character saved-day column. The failure was reproduced as HTTP 500 before the fix. Fresh schema and repeatable migration now allow 200 characters; the regression generates, saves and reopens the exact long-name plan. Existing saved data is retained.
2. **Rest slots displayed a stray zero.** React rendered the numeric zero from a duration conditional. The result page now displays duration text only for positive durations. Final screenshots were inspected after the correction.

The final Docker run passed **56 API checks** and **all five browser journeys** after these fixes. It also built the production frontend and exercised fresh database initialization, container health, persistence and the alternate port. Local native checks additionally repeated the migration/legacy-data upgrade and React forms against the API.

## What the result can assure

The current implementation works as a catalog-based itinerary planning outline under its defined rules. Admin data changes feed new generations without editing planner code, and saved snapshots preserve historical plans. Starter catalog prices and durations remain illustrative estimates; seasons/advisories are broad tags, not current forecasts.

This audit does not verify physical GPS hardware, an actual phone, Safari/Firefox, a public hosting environment, sustained load, live hotel inventory, booking, current road access/safety or actual travel times. Origin affects ordering; travel from the origin to the first stop is not scheduled. Free time is an intentional result when the catalog or allowance cannot supply another activity. Those are model/scope limits, not hidden promises covered by the passing tests.

See [VERIFICATION.md](VERIFICATION.md) for repeatable commands and [CATALOG.md](CATALOG.md) for data provenance.
