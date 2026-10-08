const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/browser',
  workers: 1,
  retries: 0,
  timeout: 240000,
  expect: { timeout: 15000 },
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.TEST_WEB_URL || 'http://127.0.0.1:5180',
    viewport: { width: 1440, height: 1000 },
    browserName: 'chromium',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
});
