// Applications, vehicles, fines, notifications, two-factor sign-in, exports, statistics.
const crypto = require('crypto');
const { test, expect } = require('@playwright/test');
const { signIn, flash, submitForm, csrfToken } = require('./helpers');

test.describe.configure({ mode: 'serial' });

// --- Authenticator-app maths, so the test can produce real codes ---
function base32Decode(text) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const c of text.replace(/[^A-Z2-7]/gi, '').toUpperCase()) bits += alphabet.indexOf(c).toString(2).padStart(5, '0');
  const bytes = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
  return Buffer.from(bytes);
}
function totp(secret, stepOffset = 0) {
  const step = Math.floor(Date.now() / 1000 / 30) + stepOffset;
  const msg = Buffer.alloc(8);
  msg.writeBigUInt64BE(BigInt(step));
  const hash = crypto.createHmac('sha1', base32Decode(secret)).update(msg).digest();
  const offset = hash[19] & 0xf;
  return String((hash.readUInt32BE(offset) & 0x7fffffff) % 1e6).padStart(6, '0');
}

test('civilian updates their own contact details', async ({ page }) => {
  await signIn(page, 'john');
  await page.click('text=Update my details');
  await page.fill('#f-phone', '+371 2999 0000');
  await submitForm(page);
  await expect(flash(page)).toContainText('saved');
  await expect(page.locator('.facts')).toContainText('+371 2999 0000');
  // Name can't be changed here.
  await page.goto('me_edit.php');
  await expect(page.locator('#f-first_name')).toHaveCount(0);
});

test('civilian applies for a business and a vehicle', async ({ page }) => {
  await signIn(page, 'john');
  await page.goto('applications.php');
  await page.click('.choice:has-text("Business registration")');
  await page.fill('#f-name', 'Doe Bakery');
  await page.fill('#f-business_type', 'Bakery');
  await page.fill('#f-address', '5 Bread St');
  await submitForm(page);
  await expect(flash(page)).toContainText('was sent');

  await page.click('.choice:has-text("Vehicle registration")');
  await page.fill('#f-make', 'Toyota');
  await page.fill('#f-model', 'Yaris');
  await page.fill('#f-year', '1800');
  await page.fill('#f-vin', 'JTDBT123456789012');
  await submitForm(page);
  await expect(page.locator('#f-year-error')).toBeVisible();
  await page.fill('#f-year', '2019');
  await submitForm(page);
  await expect(flash(page)).toContainText('was sent');
  await expect(page.locator('.application-list li')).toHaveCount(3); // plus the seeded license application
});

test('registrar is notified and decides applications', async ({ page }) => {
  await signIn(page, 'registrar');
  await expect(page.locator('.nav-count')).toBeVisible();
  await page.goto('registry/applications.php');
  const cards = page.locator('.warrant');
  await expect(cards).toHaveCount(3);

  // Reject needs a reason.
  const business = cards.filter({ hasText: 'Doe Bakery' });
  await business.locator('button[value=reject]').click();
  await expect(flash(page)).toContainText('Write a reason');
  await page.locator('.warrant', { hasText: 'Doe Bakery' }).locator('input[name=note]').fill('Address is outside the city. Apply at the county office.');
  await page.locator('.warrant', { hasText: 'Doe Bakery' }).locator('button[value=reject]').click();
  await expect(flash(page)).toContainText('rejected');

  await page.locator('.warrant', { hasText: 'Motorcycle' }).locator('button[value=approve]').click();
  await expect(flash(page)).toContainText('License MOT-');
  await page.locator('.warrant', { hasText: 'Toyota' }).locator('button[value=approve]').click();
  await expect(flash(page)).toContainText('plate');
  await expect(page.locator('.empty')).toContainText('No applications are waiting');
});

test('civilian sees the decisions', async ({ page }) => {
  await signIn(page, 'john');
  await page.goto('notifications.php');
  await expect(page.locator('.is-unread')).toHaveCount(3);
  await page.locator('.notification-list a', { hasText: 'rejected' }).click();
  await expect(page).toHaveURL(/applications\.php$/);
  await expect(page.locator('.decision-note').first()).toBeVisible();
  await page.goto('me.php');
  await expect(page.locator('section:has(h2:text("Licenses"))')).toContainText('MOT-');
  await expect(page.locator('section:has(h2:text("Vehicles"))')).toContainText('Toyota Yaris');
});

test('plate check flags problems', async ({ page }) => {
  await signIn(page, 'officer');
  await page.goto('police/plate.php?plate=mo 1985');
  await expect(page.locator('.alert-item-bad')).toContainText('Reported stolen');
  await page.goto('police/plate.php?plate=JD1980');
  await expect(page.locator('.plate-badge')).toHaveText('JD-1980');
  await expect(page.locator('.alerts-list')).toContainText('active warrant');
  await page.goto('police/plate.php?plate=ZZ-0000');
  await expect(page.locator('.empty')).toContainText('No vehicle');
});

test('officer issues a fine from a plate check', async ({ page }) => {
  await signIn(page, 'officer');
  await page.goto('police/plate.php?plate=JD-1980');
  await page.click('text=Issue fine');
  await expect(page.locator('#f-civilian_id-search')).toHaveValue(/Doe, John/);
  await page.fill('#f-amount', 'abc');
  await page.fill('#f-reason', 'Speeding, 72 km/h in a 50 zone');
  await submitForm(page);
  await expect(page.locator('#f-amount-error')).toBeVisible();
  await page.fill('#f-amount', '45,50');
  await submitForm(page);
  await expect(flash(page)).toContainText('issued');
  await expect(page.locator('section:has(h2:text("Fines"))')).toContainText('€45.50');

  // Officers can't record payments.
  await page.goto('police/fines.php');
  const token = await csrfToken(page);
  const r = await page.request.post('police/fines.php', { form: { _token: token, _action: 'paid', id: '1' } });
  expect(r.status()).toBe(403);
});

test('civilian sees how to pay, registrar records payment', async ({ page }) => {
  await signIn(page, 'john');
  await expect(page.locator('.pay-box')).toContainText('payment reference');

  await signIn(page, 'registrar');
  await page.goto('police/fines.php?q=72+km');
  await page.locator('tr', { hasText: '72 km/h' }).locator('button', { hasText: 'Record payment' }).click();
  await page.locator('dialog .btn-danger').click();
  await expect(flash(page)).toContainText('marked as paid');
});

test('officer reports a vehicle stolen and the owner is notified', async ({ page }) => {
  await signIn(page, 'officer');
  await page.goto('police/plate.php?plate=JD-1980');
  await page.click('text=Vehicle details');
  await page.locator('button', { hasText: 'Report stolen' }).click();
  await page.locator('dialog .btn-danger').click();
  await expect(flash(page)).toContainText('reported stolen');
  await expect(page.locator('#f-make')).toBeDisabled(); // officers can't edit vehicle details

  await signIn(page, 'john');
  await page.goto('notifications.php');
  await expect(page.locator('.notification-list')).toContainText('JD-1980 was reported stolen');
});

test('judge hears about warrant requests', async ({ page }) => {
  await signIn(page, 'officer');
  await page.goto('police/warrant_form.php?civilian_id=2');
  await page.fill('#f-reason', 'Unpaid court fine');
  await submitForm(page);
  await signIn(page, 'judge');
  await expect(page.locator('.nav-count')).toBeVisible();
  await page.goto('notifications.php');
  const newest = page.locator('.notification-list li').first();
  await expect(newest).toHaveClass(/is-unread/);
  await expect(newest).toContainText('needs a decision');
  await newest.locator('a').click();
  await expect(page).toHaveURL(/status=pending/);
  await page.goto('notifications.php');
  await expect(page.locator('.notification-list li').first()).not.toHaveClass(/is-unread/);
  await page.click('button:has-text("Mark all as read")');
  await expect(page.locator('.is-unread')).toHaveCount(0);
  await expect(page.locator('.nav-count')).toHaveCount(0);
});

let secret;
let recovery;

test('judge turns on two-factor sign-in', async ({ page }) => {
  await signIn(page, 'judge');
  await page.goto('account.php');
  await page.click('text=Set up two-factor sign-in');
  await expect(page.locator('.qr img')).toBeVisible();
  await page.click('text=Can\'t scan?');
  secret = (await page.locator('.setup-key').textContent()).replace(/\s/g, '');
  await page.fill('#f-code', '000000');
  await page.click('button:has-text("Turn on")');
  await expect(page.locator('#f-code-error')).toBeVisible();
  await page.fill('#f-code', totp(secret));
  await page.click('button:has-text("Turn on")');
  await expect(page.locator('.recovery-codes li')).toHaveCount(10);
  recovery = (await page.locator('.recovery-codes li').first().textContent()).trim();
});

test('signing in then asks for a code', async ({ page }) => {
  await signIn(page, 'judge');
  await expect(page).toHaveURL(/login_2fa\.php$/);
  // Not signed in yet.
  await page.goto('court/files.php');
  await expect(page).toHaveURL(/login\.php$/);

  await signIn(page, 'judge');
  await page.fill('#code', '123456');
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-error')).toContainText('wrong or expired');
  // The code used to turn it on can't be reused; the next one is accepted.
  await page.fill('#code', totp(secret, 1));
  await page.click('button[type=submit]');
  await expect(page).toHaveURL(/index\.php$/);
});

test('a recovery code works once', async ({ page }) => {
  await signIn(page, 'judge');
  await page.click('text=I can\'t use my phone');
  await page.fill('#code', recovery);
  await page.click('button[type=submit]');
  await expect(flash(page)).toContainText('9 left');

  await signIn(page, 'judge');
  await page.click('text=I can\'t use my phone');
  await page.fill('#code', recovery);
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-error')).toContainText('already used');
});

test('admin can reset two-factor for someone who lost their phone', async ({ page }) => {
  await signIn(page, 'admin');
  await page.goto('admin/users.php?q=judge');
  await page.click('a.row-link:has-text("judge")');
  await page.locator('button', { hasText: 'Reset two-factor' }).click();
  await page.locator('dialog .btn-danger').click();
  await expect(flash(page)).toContainText('Two-factor sign-in is off');
  await signIn(page, 'judge');
  await expect(page).toHaveURL(/index\.php$/);
});

test('registrar exports CSV safely; officers cannot export', async ({ page }) => {
  await signIn(page, 'registrar');
  await page.goto('registry/civilian_form.php');
  await page.fill('#f-first_name', '=HYPERLINK("http://evil")');
  await page.fill('#f-last_name', 'Formula');
  await page.fill('#f-dob', '1990-05-05');
  await page.selectOption('#f-gender', 'other');
  await submitForm(page);

  const response = await page.request.get('registry/civilians.php?export=csv&q=Formula');
  expect(response.headers()['content-type']).toContain('text/csv');
  const csv = await response.text();
  expect(csv).toContain('"Person code","First name"');
  expect(csv).toContain(`"'=HYPERLINK(""http://evil"")"`);

  await signIn(page, 'officer');
  const denied = await page.request.get('registry/vehicles.php?export=csv');
  expect(denied.status()).toBe(403);
});

test('statistics are for administrators', async ({ page }) => {
  await signIn(page, 'admin');
  await page.goto('admin/reports.php');
  await expect(page.locator('.tile')).toHaveCount(8);
  expect(await page.locator('.chart-bar').count()).toBeGreaterThan(10);
  await signIn(page, 'registrar');
  expect((await page.goto('admin/reports.php')).status()).toBe(403);
});

test('activity log records lookups and exports', async ({ page }) => {
  await signIn(page, 'admin');
  await page.goto('admin/activity.php?kind=access');
  await expect(page.locator('tbody')).toContainText('Plate check: JD-1980');
  await expect(page.locator('tbody')).toContainText('rows of civilians as CSV');
});
