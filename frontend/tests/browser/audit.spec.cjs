const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const { writeFileSync, readFileSync } = require('node:fs');
const path = require('node:path');
const repo = path.resolve(__dirname, '../../..');

test.beforeAll(() => {
  if (process.env.TEST_ALLOW_WRITES !== '1') throw Error('Use a disposable test database with TEST_ALLOW_WRITES=1.');
});
function cleanup(email, name) {
  execFileSync('docker', ['compose', 'exec', '-T', '-e', 'TEST_ALLOW_WRITES=1', 'api', 'php', 'tests/browser-fixtures.php', 'cleanup', email, name],
    { cwd: repo, encoding: 'utf8', timeout: 30000 });
}
async function login(page, email, password, target = /\/dashboard$/) {
  await page.goto('/login');
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password', { exact: true }).fill(password);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(target);
}
async function register(page, email) {
  await page.getByLabel('Your name').fill('Audit Traveler');
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password · at least 8 characters').fill('Browser@123');
  await page.getByLabel('Confirm password').fill('Browser@123');
  await page.getByRole('button', { name: 'Create account', exact: true }).click();
}
async function screenshot(page, info, name) {
  await page.evaluate(() => document.fonts.ready);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Page should fit the viewport').toBeTruthy();
  await page.screenshot({ path: info.outputPath(name + '.png'), fullPage: true });
}
async function prepare(page, options) {
  await page.goto('/generator');
  for (const name of options.names) await page.locator('.planner-option').filter({ has: page.getByText(name, { exact: true }) }).click();
  await page.getByRole('button', { name: 'Continue', exact: true }).click();
  await page.getByLabel('Start date').fill(options.start || '2027-10-10');
  await page.getByLabel('Number of days').fill(String(options.days));
  await page.getByRole('button', { name: 'Continue', exact: true }).click();
  await page.getByLabel('Trip budget · NPR').fill(String(options.budget));
  await page.getByRole('button', { name: 'Continue', exact: true }).click();
  if (options.interests) {
    for (const button of await page.locator('.planner-options--interests button').all()) {
      const wanted = options.interests.includes((await button.innerText()).trim());
      if (wanted && await button.getAttribute('aria-pressed') !== 'true') await button.click();
    }
    for (const button of await page.locator('.planner-options--interests button').all()) {
      if (!options.interests.includes((await button.innerText()).trim()) && await button.getAttribute('aria-pressed') === 'true') await button.click();
    }
  }
}
async function generate(page) {
  const response = page.waitForResponse(r => r.url().endsWith('/itinerary/generate.php') && r.request().method() === 'POST');
  await page.getByRole('button', { name: 'Create my plan' }).click();
  const result = await response;
  expect(result.ok(), await result.text()).toBeTruthy();
  const plan = (await result.json()).data;
  await expect(page).toHaveURL(/\/itinerary-result$/);
  await expect(page.locator('.day-card')).toHaveCount(plan.total_days);
  return plan;
}
async function save(page) {
  const response = page.waitForResponse(r => r.url().endsWith('/itinerary/save.php'));
  await page.getByRole('button', { name: 'Save this plan' }).click();
  const result = await response;
  expect(result.ok()).toBeTruthy();
  return String((await result.json()).data.itinerary_id);
}
const cents = value => Math.round(Number(value) * 100);
// Independent spherical cosine calculation, rather than the PHP Haversine implementation.
function distance(a, b) {
  const rad = value => Number(value) * Math.PI / 180;
  const cosine = Math.sin(rad(a.latitude)) * Math.sin(rad(b.latitude)) +
    Math.cos(rad(a.latitude)) * Math.cos(rad(b.latitude)) * Math.cos(rad(a.longitude) - rad(b.longitude));
  return 6371 * Math.acos(Math.max(-1, Math.min(1, cosine)));
}
function validatePlan(plan, selected, activities) {
  const route = plan.map_data.destinations;
  expect(route.map(d => d.id).sort((a, b) => a - b)).toEqual(selected.map(d => Number(d.id)).sort((a, b) => a - b));
  const pending = [...selected];
  let previous = plan.origin || pending.shift();
  const expected = plan.origin ? [] : [Number(previous.id)];
  while (pending.length) {
    const next = pending.reduce((best, candidate) => distance(previous, candidate) < distance(previous, best) ? candidate : best);
    expected.push(Number(next.id));
    previous = next;
    pending.splice(pending.indexOf(next), 1);
  }
  expect(route.map(d => d.id)).toEqual(expected);
  for (let i = 1; i < route.length; i++) {
    expect(Math.abs(plan.route_legs[i - 1].distance_km - distance(route[i - 1], route[i]))).toBeLessThan(0.06);
  }
  const byId = new Map(activities.map(a => [Number(a.id), a]));
  const seen = new Set();
  const allowance = Math.floor(cents(plan.budget_summary.total_budget) / plan.total_days);
  let sum = 0;
  expect(plan.days.length).toBe(plan.total_days);
  for (const [index, day] of plan.days.entries()) {
    const date = new Date(plan.start_date + 'T00:00:00Z');
    date.setUTCDate(date.getUTCDate() + index);
    expect(day.date).toBe(date.toISOString().slice(0, 10));
    const month = date.getUTCMonth() + 1;
    const season = month >= 3 && month <= 5 ? 'Spring' : month >= 6 && month <= 9 ? 'Summer' : month >= 10 && month <= 11 ? 'Autumn' : 'Winter';
    expect(day.season).toBe(season);
    let daySum = cents(day.accommodation.cost);
    for (const [slotName, slot] of Object.entries(day.slots)) {
      expect(slot.duration).toBeGreaterThanOrEqual(0);
      expect(slot.duration).toBeLessThanOrEqual(slotName === 'evening' ? 3 : 4);
      daySum += cents(slot.cost);
      if (slot.activity_id != null) {
        expect(seen.has(slot.activity_id)).toBeFalsy();
        seen.add(slot.activity_id);
        const record = byId.get(slot.activity_id);
        expect(record, 'Chosen activity must exist in the live catalog').toBeTruthy();
        expect(Number(record.destination_id)).toBe(day.destination_id);
        expect(record.suitable_seasons.split(',')).toContain(season);
        expect(cents(slot.cost)).toBe(cents(record.cost_npr));
        expect(slot.total_duration).toBe(Number(record.duration_hours));
      } else expect(cents(slot.cost)).toBe(0);
    }
    expect(daySum).toBe(cents(day.day_total));
    expect(daySum).toBeLessThanOrEqual(allowance);
    sum += daySum;
  }
  expect(sum).toBe(cents(plan.budget_summary.total_estimated));
  expect(sum).toBeLessThanOrEqual(cents(plan.budget_summary.total_budget));
  expect(cents(plan.budget_summary.remaining)).toBe(cents(plan.budget_summary.total_budget) - sum);
  expect(plan.end_date).toBe(plan.days.at(-1).date);
}

test('audit: public pages, account errors, home prefill, algorithm boundaries and recovery', async ({ page, context }, info) => {
  const tag = String(Date.now());
  const email = 'browser_' + tag + '@example.com';
  const errors = [];
  page.on('pageerror', e => errors.push(e.stack || e.message));
  const evidence = [];
  try {
    await page.goto('/');
    await expect(page.getByLabel('First stop').locator('option')).not.toHaveCount(1);
    await screenshot(page, info, 'home-desktop');
    await page.goto('/destinations');
    await expect(page.locator('.explore-card')).not.toHaveCount(0);
    await screenshot(page, info, 'catalog-desktop');
    await page.getByLabel('Search destinations').fill('no such place 987654321');
    await expect(page.getByText('No destinations match that search.')).toBeVisible();
    await page.goto('/login');
    await screenshot(page, info, 'login-desktop');
    await page.getByLabel('Email address').fill('traveler@yatra.com');
    await page.getByLabel('Password', { exact: true }).fill('WrongPassword123');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page).toHaveURL(/\/login$/);
    await page.goto('/a-page-that-does-not-exist');
    await expect(page.locator('h1')).toContainText('path');
    await screenshot(page, info, 'not-found-desktop');
    await page.goto('/');
    await page.getByLabel('First stop').selectOption({ label: 'Pokhara' });
    await page.getByLabel('Days away').fill('2');
    await page.getByLabel('Estimated budget · NPR').fill('6000');
    await page.getByRole('button', { name: 'Continue to planner' }).click();
    await expect(page).toHaveURL(/\/login$/);
    await page.getByRole('link', { name: 'Create an account', exact: true }).click();
    await screenshot(page, info, 'register-desktop');
    await page.getByLabel('Your name').fill('Audit Traveler');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password · at least 8 characters').fill('Browser@123');
    await page.getByLabel('Confirm password').fill('Different123');
    await page.getByRole('button', { name: 'Create account', exact: true }).click();
    await expect(page.getByRole('alert')).toContainText('passwords do not match');
    await register(page, email);
    await expect(page).toHaveURL(/\/generator$/);
    await expect(page.locator('.planner-option').filter({ has: page.getByText('Pokhara', { exact: true }) })).toHaveAttribute('aria-pressed', 'true');
    await page.getByRole('button', { name: 'Continue', exact: true }).click();
    await expect(page.getByLabel('Number of days')).toHaveValue('2');
    await page.getByLabel('Start date').fill('2027-11-30');
    await page.getByRole('button', { name: 'Continue', exact: true }).click();
    await expect(page.getByLabel('Trip budget · NPR')).toHaveValue('6000');
    await page.getByLabel('Trip budget · NPR').fill('2999');
    await page.getByRole('button', { name: 'Continue', exact: true }).click();
    await expect(page.getByRole('alert')).toContainText('1,500 per day');
    await page.getByLabel('Trip budget · NPR').fill('6000');
    await page.getByRole('button', { name: 'Continue', exact: true }).click();
    const places = (await (await context.request.get('/api/destinations/index.php')).json()).data;
    const activities = (await (await context.request.get('/api/activities/index.php')).json()).data;
    const crossing = await generate(page);
    validatePlan(crossing, places.filter(d => d.name === 'Pokhara'), activities);
    expect(crossing.days.map(d => d.season)).toEqual(['Autumn', 'Winter']);
    evidence.push({ scenario: 'season crossing / home prefill', plan: crossing });
    const id = await save(page);
    await page.reload();
    await expect(page.locator('.day-card')).toHaveCount(2);
    await page.goto('/dashboard');
    await expect(page.locator('.account-overview strong').first()).toHaveText('1');
    await screenshot(page, info, 'traveler-dashboard');
    await page.goto('/my-itineraries');
    await expect(page.locator('.trip-card')).toHaveCount(1);
    await screenshot(page, info, 'saved-trips');
    const downloadEvent = page.waitForEvent('download');
    await page.getByRole('button', { name: 'PDF', exact: true }).click();
    const download = await downloadEvent;
    const pdf = info.outputPath('saved-plan.pdf');
    await download.saveAs(pdf);
    expect(readFileSync(pdf).subarray(0, 5).toString()).toBe('%PDF-');
    page.once('dialog', d => d.dismiss());
    await page.locator('.trip-card__delete').click();
    await expect(page.locator('.trip-card')).toHaveCount(1);

    const scenarios = [
      { label: 'minimum daily budget', names: ['Pokhara'], days: 1, budget: 1500, tier: 'Budget' },
      { label: 'below midrange', names: ['Pokhara'], days: 1, budget: 3499.99, tier: 'Budget', interests: ['Adventure & Trekking'] },
      { label: 'at midrange', names: ['Pokhara'], days: 1, budget: 3500, tier: 'Mid-Range' },
      { label: 'below luxury', names: ['Pokhara'], days: 1, budget: 10000, tier: 'Mid-Range' },
      { label: 'at luxury', names: ['Pokhara'], days: 1, budget: 10000.01, tier: 'Luxury' },
      { label: 'thirty days, catalog exhausted', names: ['Pokhara'], days: 30, budget: 45000, tier: 'Budget' },
      { label: 'four stops, no origin', names: ['Kathmandu Valley', 'Pokhara', 'Lumbini', 'Janakpur'], days: 12, budget: 90000, tier: 'Mid-Range' },
    ];
    for (const scenario of scenarios) {
      await test.step(scenario.label, async () => {
        await prepare(page, scenario);
        const plan = await generate(page);
        validatePlan(plan, scenario.names.map(name => places.find(d => d.name === name)), activities);
        expect(plan.budget_summary.tier).toBe(scenario.tier);
        if (scenario.interests) expect(plan.interests).toEqual(['adventure']);
        if (scenario.days === 30) {
          await page.locator('.day-card').last().scrollIntoViewIfNeeded();
          await expect(page.locator('.day-card').last()).toContainText('Free time');
          await page.screenshot({ path: info.outputPath('day-thirty.png') });
        }
        evidence.push({ scenario: scenario.label, plan });
      });
    }
    await screenshot(page, info, 'four-stop-result');
    await prepare(page, { names: ['Kathmandu Valley', 'Pokhara'], days: 2, budget: 6000 });
    const rejected = page.waitForResponse(r => r.url().endsWith('/itinerary/generate.php'));
    await page.getByRole('button', { name: 'Create my plan' }).click();
    expect((await rejected).status()).toBe(422);
    await expect(page.getByRole('alert')).toContainText('including transfer allowances');
    await page.getByRole('button', { name: /Dates/ }).click();
    await page.getByLabel('Number of days').fill('5');
    await page.getByRole('button', { name: 'Continue', exact: true }).click();
    await page.getByLabel('Trip budget · NPR').fill('15000');
    await page.getByRole('button', { name: 'Continue', exact: true }).click();
    const recovered = await generate(page);
    validatePlan(recovered, ['Kathmandu Valley', 'Pokhara'].map(name => places.find(d => d.name === name)), activities);
    evidence.push({ scenario: 'short-route validation recovered', plan: recovered });
    await page.route('**/api/destinations/index.php*', route => route.abort('failed'), { times: 1 });
    await page.goto('/generator');
    await expect(page.getByRole('alert')).toContainText('catalog could not be loaded');
    await expect(page.getByRole('button', { name: 'Continue', exact: true })).toBeDisabled();
    await page.reload();
    await expect(page.locator('.planner-option')).not.toHaveCount(0);
    await page.goto('/my-itineraries');
    page.once('dialog', d => d.accept());
    await page.locator('.trip-card__delete').click();
    await expect(page.getByText('Trip deleted.', { exact: true })).toBeVisible();
    await page.goto('/itinerary/' + id);
    await expect(page.getByRole('heading', { name: 'We could not find that plan.' })).toBeVisible();
    await page.goto('/dashboard');
    await expect(page.getByText('You have not saved a plan yet.')).toBeVisible();
    await screenshot(page, info, 'empty-dashboard');
    await page.goto('/admin');
    await expect(page).toHaveURL(/\/dashboard$/);
    expect(errors, 'Uncaught runtime errors in audit journeys').toEqual([]);
    writeFileSync(info.outputPath('algorithm-scenarios.json'), JSON.stringify(evidence, null, 2));
  } finally {
    cleanup(email, 'Browser catalog ' + tag);
  }
});

test('audit: admin changes feed the planner, saved history survives edits/deletion, deactivation revokes access', async ({ browser, baseURL }, info) => {
  const traveler = await browser.newContext({ baseURL });
  const administrator = await browser.newContext({ baseURL });
  const user = await traveler.newPage();
  const admin = await administrator.newPage();
  const tag = String(Date.now());
  const email = 'browser_' + tag + '@example.com';
  const name = 'Browser catalog ' + tag;
  const activity = 'Live catalog activity ' + tag;
  const errors = [];
  for (const page of [user, admin]) page.on('pageerror', e => errors.push(e.stack || e.message));
  try {
    await user.goto('/register');
    await register(user, email);
    await expect(user).toHaveURL(/\/dashboard$/);
    await login(admin, 'admin@yatra.com', 'Admin@123', /\/admin$/);
    await admin.goto('/admin/destinations');
    await admin.getByRole('button', { name: 'Add destination', exact: true }).click();
    await admin.getByLabel('Name', { exact: true }).fill(name);
    await admin.getByLabel('Description', { exact: true }).fill('Isolated catalog destination for live browser verification.');
    await admin.getByLabel('Latitude', { exact: true }).fill('27.7');
    await admin.getByLabel('Longitude', { exact: true }).fill('85.3');
    await admin.getByRole('button', { name: 'Save record' }).click();
    await expect(admin.getByText('Record created.', { exact: true })).toBeVisible();
    await admin.goto('/admin/activities');
    await admin.getByRole('button', { name: 'Add activity', exact: true }).click();
    await admin.getByLabel('Name', { exact: true }).fill(activity);
    await admin.locator('form').getByLabel('Destination').selectOption({ label: name });
    await admin.getByLabel('Cost estimate · NPR').fill('500');
    await admin.getByRole('button', { name: 'Save record' }).click();
    await expect(admin.getByText('Record created.', { exact: true })).toBeVisible();
    await prepare(user, { names: [name], days: 1, budget: 3000 });
    const original = await generate(user);
    expect(original.days[0].slots.morning.activity).toBe(activity);
    expect(original.days[0].day_total).toBe(2000);
    const id = await save(user);
    await admin.getByLabel('Search', { exact: true }).fill(activity);
    await admin.getByRole('row').filter({ hasText: activity }).getByRole('button', { name: 'Edit', exact: true }).click();
    await admin.getByLabel('Cost estimate · NPR').fill('700');
    await admin.getByRole('button', { name: 'Save record' }).click();
    await expect(admin.getByText('Record updated.', { exact: true })).toBeVisible();
    await prepare(user, { names: [name], days: 1, budget: 3000 });
    const updated = await generate(user);
    expect(updated.days[0].slots.morning.cost).toBe(700);
    expect(updated.days[0].day_total).toBe(2200);
    await user.goto('/itinerary/' + id);
    await expect(user.locator('.day-card')).toContainText(activity);
    const preserved = (await (await traveler.request.get('/api/itinerary/fetch.php?id=' + id)).json()).data;
    expect(preserved.days).toEqual(original.days);
    await admin.goto('/admin');
    await expect(admin.locator('.admin-metrics strong').nth(3)).toHaveText('1');
    await expect(admin.locator('.recharts-surface').first()).toBeVisible();
    await screenshot(admin, info, 'admin-dashboard');
    await admin.goto('/admin/destinations');
    await admin.getByLabel('Search', { exact: true }).fill(name);
    await screenshot(admin, info, 'admin-destinations');
    admin.once('dialog', d => d.accept());
    await admin.getByRole('row').filter({ hasText: name }).getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(admin.getByText('Record deleted.', { exact: true })).toBeVisible();
    await user.reload();
    await expect(user.locator('.day-card')).toContainText(activity);
    const afterDelete = (await (await traveler.request.get('/api/itinerary/fetch.php?id=' + id)).json()).data;
    expect(afterDelete.days).toEqual(original.days);
    expect(afterDelete.map_data).toEqual(original.map_data);
    await screenshot(user, info, 'preserved-history');
    await user.goto('/generator');
    await expect(user.getByText(name, { exact: true })).toHaveCount(0);
    await admin.goto('/admin/activities');
    await admin.getByLabel('Search', { exact: true }).fill(activity);
    await expect(admin.getByText('No matching records.', { exact: true })).toBeVisible();
    await screenshot(admin, info, 'admin-activities');
    await admin.goto('/admin/travelers');
    await admin.getByLabel('Search', { exact: true }).fill(email);
    await screenshot(admin, info, 'admin-travelers');
    await admin.getByRole('row').filter({ hasText: email }).getByRole('button', { name: 'Deactivate' }).click();
    await expect(admin.getByText('Traveler status updated.', { exact: true })).toBeVisible();
    await user.reload();
    await expect(user).toHaveURL(/\/login$/);
    await login(admin, 'admin@yatra.com', 'Admin@123', /\/admin$/);
    await user.getByLabel('Email address').fill(email);
    await user.getByLabel('Password', { exact: true }).fill('Browser@123');
    await user.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(user.getByRole('alert')).toBeVisible();
    await expect(user).toHaveURL(/\/login$/);
    expect(errors, 'Uncaught runtime errors in live catalog journeys').toEqual([]);
    writeFileSync(info.outputPath('catalog-snapshots.json'), JSON.stringify({ original, updated, afterDelete }, null, 2));
  } finally {
    cleanup(email, name);
    await traveler.close();
    await administrator.close();
  }
});

test('audit: mobile public pages, navigation and traveler account pages', async ({ browser, baseURL }, info) => {
  const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.stack || e.message));
  try {
    await page.goto('/');
    await expect(page.getByLabel('First stop').locator('option')).not.toHaveCount(1);
    await screenshot(page, info, 'home-mobile');
    await page.getByRole('button', { name: 'Open menu' }).click();
    await expect(page.getByRole('button', { name: 'Close menu' })).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press('Escape');
    await expect(page.getByRole('button', { name: 'Open menu' })).toHaveAttribute('aria-expanded', 'false');
    await page.getByRole('button', { name: 'Open menu' }).click();
    await page.getByRole('navigation', { name: 'Main navigation' }).getByRole('link', { name: 'Explore', exact: true }).click();
    await expect(page).toHaveURL(/\/destinations$/);
    await expect(page.getByRole('button', { name: 'Open menu' })).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator('.explore-card')).not.toHaveCount(0);
    await screenshot(page, info, 'catalog-mobile');
    await page.locator('.explore-card').filter({ has: page.getByRole('heading', { name: 'Pokhara', exact: true }) }).getByRole('link', { name: 'Activities & reviews' }).click();
    await expect(page.getByRole('heading', { name: 'Activities in the catalog.' })).toBeVisible();
    await screenshot(page, info, 'destination-mobile');
    await page.goto('/register');
    await screenshot(page, info, 'register-mobile');
    await page.goto('/login');
    await screenshot(page, info, 'login-mobile');
    await login(page, 'traveler@yatra.com', 'Traveler@123');
    await expect(page.getByText('You have not saved a plan yet.')).toBeVisible();
    await screenshot(page, info, 'dashboard-mobile');
    await page.goto('/my-itineraries');
    await expect(page.getByRole('heading', { name: 'No saved trips yet.' })).toBeVisible();
    await screenshot(page, info, 'saved-trips-mobile');
    expect(errors, 'Uncaught runtime errors on mobile pages').toEqual([]);
  } finally {
    await context.close();
  }
});
