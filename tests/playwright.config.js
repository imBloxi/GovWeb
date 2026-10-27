// Run against a server with freshly imported schema.sql + seed.sql:
//   APP_DEBUG=1 php -S 127.0.0.1:8080        (from the project root)
//   cd tests && npm install && npm test
// Set BASE_URL to test another address, e.g. http://localhost/GovWeb
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './e2e',
  // Tests share one database and build on each other's data, so run them in order.
  workers: 1,
  fullyParallel: false,
  retries: 0,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    baseURL: (process.env.BASE_URL || 'http://127.0.0.1:8080').replace(/\/$/, '') + '/',
    trace: 'retain-on-failure',
  },
});
