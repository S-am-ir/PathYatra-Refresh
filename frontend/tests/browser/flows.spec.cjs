const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const repo = path.resolve(__dirname, '../../..');

test.beforeAll(() => {
  if (process.env.TEST_ALLOW_WRITES !== '1') {
    throw Error('Set TEST_ALLOW_WRITES=1 only against a disposable/local Compose installation.');
  }
});

function compose(...args) {
  return execFileSync('docker', ['compose', ...args], { cwd: repo, encoding: 'utf8', timeout: 180000 });
}
function fixture(...args) {
  compose('exec', '-T', '-e', 'TEST_ALLOW_WRITES=1', 'api', 'php', 'tests/browser-fixtures.php', ...args);
}
async function login(page, email, password, target = /\/dashboard$/) {
  await page.goto('/login');
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password', { exact: true }).fill(password);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(target);
}
async function choosePlan(page, days = 5) {
  await page.getByRole('button', { name: /Kathmandu Valley.*catalog estimate/ }).click();
  await page.getByRole('button', { name: /Pokhara.*catalog estimate/ }).click();
  await page.getByRole('button', { name: 'Continue', exact: true }).click();
  await page.getByLabel('Number of days').fill(String(days));
  await page.getByRole('button', { name: 'Continue', exact: true }).click();
  await page.getByLabel('Trip budget · NPR').fill('30000');
  await page.getByRole('button', { name: 'Continue', exact: true }).click();
  const response = page.waitForResponse(r => r.url().endsWith('/itinerary/generate.php') && r.request().method() === 'POST');
  await page.getByRole('button', { name: 'Create my plan' }).click();
  const result = await response;
  expect(result.ok()).toBeTruthy();
  const plan = (await result.json()).data;
  await expect(page).toHaveURL(/\/itinerary-result$/);
  await expect(page.locator('.day-card')).toHaveCount(days);
  return plan;
}
async function assertMap(page) {
  const map = page.locator('.result-map__canvas');
  await expect(map).toBeVisible();
  await map.scrollIntoViewIfNeeded();
  // Two destination markers, an origin marker and a real SVG route line.
  await expect(map.locator('.leaflet-overlay-pane svg path')).toHaveCount(4);
  await expect(map.locator('path[stroke-dasharray="7 7"]')).toBeVisible();
  const bounds = await map.boundingBox();
  for (const marker of await map.locator('path:not([stroke-dasharray="7 7"])').all()) {
    const point = await marker.boundingBox();
    expect(point.x).toBeGreaterThanOrEqual(bounds.x);
    expect(point.y).toBeGreaterThanOrEqual(bounds.y);
    expect(point.x + point.width).toBeLessThanOrEqual(bounds.x + bounds.width);
    expect(point.y + point.height).toBeLessThanOrEqual(bounds.y + bounds.height);
  }
  await expect.poll(() => map.locator('img.leaflet-tile').evaluateAll(images =>
    images.filter(img => img.complete && img.naturalWidth > 0).length
  ), { timeout: 30000, message: 'Live OpenStreetMap tiles must actually download; no tile mocks are used.' }).toBeGreaterThan(0);
  await map.locator('.leaflet-control-zoom-in').click();
  await expect(map.locator('path[stroke-dasharray="7 7"]')).toBeVisible();
  await map.locator('.leaflet-control-zoom-out').click();
  // Capture the fitted route after tiles finish fading in, rather than a transient zoom frame.
  await expect.poll(() => map.locator('img.leaflet-tile-loaded').evaluateAll(images =>
    images.filter(img => img.complete && img.naturalWidth > 0 && Number(getComputedStyle(img).opacity) >= 0.99).length
  ), { timeout: 30000 }).toBeGreaterThan(0);
}

test('desktop: actual catalog, location, itinerary, map, PDF, persistence, reviews and admin', async ({ page, context }, testInfo) => {
  const tag = String(Date.now());
  const email = `browser_${tag}@example.com`;
  const customName = `Browser catalog ${tag}`;
  const errors = [];
  page.on('pageerror', error => errors.push(error.stack || error.message));
  await context.setGeolocation({ latitude: 28.2096, longitude: 83.9856 });
  await context.grantPermissions(['geolocation']);
  try {
    await test.step('browse the real filtered catalog and register from a protected route', async () => {
      await page.goto('/');
      await expect(page.getByRole('heading', { name: 'Find your way through Nepal.' })).toBeVisible();
      await page.goto('/destinations');
      await page.getByLabel('Filter by region').selectOption('Terai');
      await page.getByLabel('Season', { exact: true }).selectOption('Winter');
      await page.getByLabel('Activity category', { exact: true }).selectOption('cultural');
      await page.getByLabel('Maximum daily estimate').fill('3000');
      await page.locator('.explore-card').filter({ has: page.getByRole('heading', { name: 'Janakpur', exact: true }) }).getByRole('link', { name: 'Activities & reviews' }).click();
      await expect(page.getByRole('heading', { name: 'Activities in the catalog.' })).toBeVisible();
      await expect(page.getByRole('button', { name: 'Save review', exact: true })).toHaveCount(0);
      await page.goto('/generator');
      await expect(page).toHaveURL(/\/login$/);
      await page.getByRole('link', { name: 'Create an account', exact: true }).click();
      await page.getByLabel('Your name').fill('Browser Test Traveler');
      await page.getByLabel('Email address').fill(email);
      await page.getByLabel('Password · at least 8 characters').fill('Browser@123');
      await page.getByLabel('Confirm password').fill('Browser@123');
      await page.getByRole('button', { name: 'Create account', exact: true }).click();
      await expect(page).toHaveURL(/\/generator$/);
    });
    let plan, id;
    await test.step('get browser location, generate and render the map, then download the PDF', async () => {
      await page.getByRole('button', { name: 'Use my current location' }).click();
      await expect(page.getByText('Current location set as your origin.', { exact: true })).toBeVisible();
      plan = await choosePlan(page);
      expect(plan.map_data.destinations[0].name).toBe('Pokhara');
      expect(plan.origin.label).toBe('Current location');
      await assertMap(page);
      await page.screenshot({ path: testInfo.outputPath('result-desktop.png'), fullPage: true });
      const downloaded = page.waitForEvent('download');
      await page.getByRole('button', { name: 'Download PDF' }).click();
      const download = await downloaded;
      expect(await download.failure()).toBeNull();
      const file = testInfo.outputPath('browser-plan.pdf');
      await download.saveAs(file);
      const bytes = readFileSync(file);
      expect(bytes.subarray(0, 5).toString()).toBe('%PDF-');
      expect(bytes.length).toBeGreaterThan(2000);
    });
    await test.step('save and reopen the exact server snapshot', async () => {
      const saved = page.waitForResponse(r => r.url().endsWith('/itinerary/save.php'));
      await page.getByRole('button', { name: 'Save this plan' }).click();
      const result = await saved;
      expect(result.ok()).toBeTruthy();
      id = String((await result.json()).data.itinerary_id);
      await page.getByRole('link', { name: 'Saved plan', exact: true }).click();
      await expect(page).toHaveURL(new RegExp(`/itinerary/${id}$`));
      await page.reload();
      await expect(page.locator('.day-card')).toHaveCount(5);
      const savedPlan = await (await context.request.get(`/api/itinerary/fetch.php?id=${id}`)).json();
      expect(savedPlan.data.days).toEqual(plan.days);
      expect(savedPlan.data.map_data).toEqual(plan.map_data);
      expect(savedPlan.data.budget_summary).toEqual(plan.budget_summary);
    });
    if (process.env.TEST_DOCKER_RESTART === '1') {
      await test.step('recreate the Compose stack and retain the database volume', async () => {
        compose('down');
        compose('up', '--detach', '--wait', '--wait-timeout', '120');
        await login(page, email, 'Browser@123');
        await page.goto(`/itinerary/${id}`);
        await expect(page.locator('.day-card')).toHaveCount(5);
        const restored = await (await context.request.get(`/api/itinerary/fetch.php?id=${id}`)).json();
        expect(restored.data.days).toEqual(plan.days);
        expect(restored.data.map_data).toEqual(plan.map_data);
        expect(restored.data.budget_summary).toEqual(plan.budget_summary);
        await assertMap(page);
      });
    }
    await test.step('enforce completion dates, review a completed trip and delete the plan', async () => {
      // Leave immediately after zooming: regression for a callback after map removal.
      await page.locator('.result-map .leaflet-control-zoom-in').click();
      await page.locator('.travel-back').click();
      await expect(page).toHaveURL(/\/my-itineraries$/);
      await page.getByRole('button', { name: 'Mark complete', exact: true }).click();
      await expect(page.getByRole('alert')).toContainText('final travel date');
      fixture('expire', email, id);
      await page.getByRole('button', { name: 'Mark complete', exact: true }).click();
      await expect(page.getByText('Trip marked complete.', { exact: true })).toBeVisible();
      await page.getByRole('link', { name: 'View plan', exact: true }).click();
      await expect(page.getByText('Your trip is complete.', { exact: false })).toBeVisible();
      await page.locator('.planner-note').getByRole('link', { name: /Pokhara/ }).click();
      await page.getByLabel('Your experience').fill('The completed itinerary was useful for planning our visit.');
      await page.getByRole('button', { name: 'Save review', exact: true }).click();
      await expect(page.getByText('Your review has been saved.', { exact: true })).toBeVisible();
      await page.getByLabel('Rating').selectOption('4');
      await page.getByRole('button', { name: 'Save review', exact: true }).click();
      await expect(page.getByText('4.00 / 5 from 1 traveler reviews', { exact: false })).toBeVisible();
      await page.goto('/my-itineraries');
      page.once('dialog', dialog => dialog.accept());
      await page.locator('.trip-card__delete').click();
      await expect(page.getByText('Trip deleted.', { exact: true })).toBeVisible();
      await expect(page.locator('.trip-card')).toHaveCount(0);
      await page.getByRole('button', { name: 'Sign out', exact: true }).last().click();
      await expect(page).toHaveURL(/\/$/);
    });
    await test.step('maintain destinations and activities and deactivate a traveler', async () => {
      await login(page, 'admin@yatra.com', 'Admin@123', /\/admin$/);
      await page.getByRole('link', { name: 'Destinations →' }).click();
      await page.getByRole('button', { name: 'Add destination', exact: true }).click();
      await page.getByLabel('Name', { exact: true }).fill(customName);
      await page.getByLabel('Description', { exact: true }).fill('A temporary browser verification destination.');
      await page.getByLabel('Latitude', { exact: true }).fill('27.7');
      await page.getByLabel('Longitude', { exact: true }).fill('85.3');
      await page.getByRole('button', { name: 'Save record' }).click();
      await expect(page.getByText('Record created.', { exact: true })).toBeVisible();
      await page.getByLabel('Search', { exact: true }).fill(customName);
      await page.getByRole('row').filter({ hasText: customName }).getByRole('button', { name: 'Edit', exact: true }).click();
      await page.getByLabel('Daily estimate · NPR').fill('2700');
      await page.getByRole('button', { name: 'Save record' }).click();
      await expect(page.getByText('Record updated.', { exact: true })).toBeVisible();
      await page.goto('/admin/activities');
      await page.getByRole('button', { name: 'Add activity', exact: true }).click();
      await page.getByLabel('Name', { exact: true }).fill(`Browser activity ${tag}`);
      await page.locator('form').getByLabel('Destination').selectOption({ label: customName });
      await page.getByRole('button', { name: 'Save record' }).click();
      await expect(page.getByText('Record created.', { exact: true })).toBeVisible();
      await page.getByLabel('Search', { exact: true }).fill(`Browser activity ${tag}`);
      page.once('dialog', dialog => dialog.accept());
      await page.getByRole('row').filter({ hasText: `Browser activity ${tag}` }).getByRole('button', { name: 'Delete', exact: true }).click();
      await expect(page.getByText('Record deleted.', { exact: true })).toBeVisible();
      await page.goto('/admin/destinations');
      await page.getByLabel('Search', { exact: true }).fill(customName);
      page.once('dialog', dialog => dialog.accept());
      await page.getByRole('row').filter({ hasText: customName }).getByRole('button', { name: 'Delete', exact: true }).click();
      await expect(page.getByText('Record deleted.', { exact: true })).toBeVisible();
      await page.goto('/admin/travelers');
      await page.getByLabel('Search', { exact: true }).fill(email);
      await page.getByRole('row').filter({ hasText: email }).getByRole('button', { name: 'Deactivate', exact: true }).click();
      await expect(page.getByText('Traveler status updated.', { exact: true })).toBeVisible();
    });
    expect(errors, 'Uncaught browser runtime errors').toEqual([]);
  } finally {
    fixture('cleanup', email, customName);
  }
});

test('mobile: browser location denial, manual origin and full result/map rendering', async ({ browser, baseURL }, testInfo) => {
  const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.stack || error.message));
  try {
    await login(page, 'traveler@yatra.com', 'Traveler@123');
    await page.goto('/generator');
    await context.clearPermissions();
    await page.getByRole('button', { name: 'Use my current location' }).click();
    await expect(page.getByText('Location could not be obtained.', { exact: false })).toBeVisible();
    await page.getByLabel('Or choose a starting place').selectOption({ label: 'Pokhara' });
    await expect(page.getByText('Origin: Pokhara', { exact: false })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('planner-mobile.png'), fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
    const plan = await choosePlan(page);
    expect(plan.map_data.destinations[0].name).toBe('Pokhara');
    expect(plan.origin.label).toBe('Pokhara');
    await assertMap(page);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('result-mobile.png'), fullPage: true });
    expect(errors, 'Uncaught mobile runtime errors').toEqual([]);
  } finally {
    await context.close();
  }
});
