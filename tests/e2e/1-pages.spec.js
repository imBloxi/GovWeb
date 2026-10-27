// Visit every page each role can reach and check none of them errors.
const { test, expect } = require('@playwright/test');
const { signIn, PHP_ERROR } = require('./helpers');

const ROLES = ['admin', 'registrar', 'officer', 'judge', 'john'];

for (const role of ROLES) {
  test(`every page loads for ${role}`, async ({ page, baseURL }) => {
    const problems = [];
    page.on('pageerror', (e) => problems.push(`JS error: ${e.message}`));
    page.on('console', (msg) => {
      if (msg.type() === 'error') problems.push(`console: ${msg.text()}`);
    });

    await signIn(page, role);
    const queue = await page.$$eval('.sidebar-nav a', (as) => as.map((a) => a.href));
    queue.push(new URL('account.php', baseURL).href);
    const seen = new Set();

    while (queue.length && seen.size < 120) {
      const href = queue.shift();
      if (seen.has(href) || !href.startsWith(baseURL) || href.includes('logout') || href.includes('export=')) continue;
      seen.add(href);
      const response = await page.goto(href);
      const html = await page.content();
      if (response.status() >= 400) problems.push(`${response.status()} ${href}`);
      if (PHP_ERROR.test(html)) problems.push(`PHP error on ${href}: ${html.match(PHP_ERROR)[0]}`);
      const links = await page.$$eval('main a', (as) => as.map((a) => a.href.split('#')[0]));
      queue.push(...links.filter((l) => !seen.has(l)));
    }

    expect(problems).toEqual([]);
    expect(seen.size).toBeGreaterThan(role === 'john' ? 2 : 10);
  });
}

test('civilians cannot use staff search', async ({ page }) => {
  await signIn(page, 'john');
  const response = await page.goto('search.php?q=a');
  expect(response.status()).toBe(403);
});

test('security headers are sent', async ({ page }) => {
  const response = await page.goto('login.php');
  const headers = response.headers();
  expect(headers['content-security-policy']).toContain("default-src 'self'");
  expect(headers['x-frame-options']).toBe('DENY');
  expect(headers['x-content-type-options']).toBe('nosniff');
});
