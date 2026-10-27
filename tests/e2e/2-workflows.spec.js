// The main jobs each role does, start to finish. Tests run in order and share data.
const { test, expect } = require('@playwright/test');
const { signIn, flash, submitForm, pickPerson, csrfToken } = require('./helpers');

test.describe.configure({ mode: 'serial' });

let personUrl;
let personId;

test('registrar registers a person', async ({ page }) => {
  await signIn(page, 'registrar');
  await page.goto('registry/civilian_form.php');
  await submitForm(page);
  expect(await page.locator('.field-error').count()).toBeGreaterThanOrEqual(4);
  await expect(page.locator('#f-dob-error')).toHaveText('Date of birth is required.');

  await page.fill('#f-first_name', 'Liga');
  await page.fill('#f-last_name', 'Test <script>');
  await page.fill('#f-dob', '1995-03-04');
  await page.selectOption('#f-gender', 'female');
  await page.fill('#f-email', 'liga@example.com');
  await submitForm(page);

  await expect(flash(page)).toContainText('950304-');
  // HTML in a name is shown as text, never run.
  await expect(page.locator('h1')).toHaveText('Liga Test <script>');
  personUrl = page.url();
  personId = new URL(personUrl).searchParams.get('id');
});

test('registering the same name and birth date asks for confirmation', async ({ page }) => {
  await signIn(page, 'registrar');
  await page.goto('registry/civilian_form.php');
  await page.fill('#f-first_name', 'Liga');
  await page.fill('#f-last_name', 'Test <script>');
  await page.fill('#f-dob', '1995-03-04');
  await page.selectOption('#f-gender', 'female');
  await submitForm(page);
  await expect(page.locator('.alert-warning')).toContainText('already in the register');

  await page.check('input[name=confirm_new_person]');
  await submitForm(page);
  await expect(flash(page)).toContainText('is registered');
});

test('duplicate email gets a clear error', async ({ page }) => {
  await signIn(page, 'registrar');
  await page.goto('registry/civilian_form.php');
  await page.fill('#f-first_name', 'Other');
  await page.fill('#f-last_name', 'Person');
  await page.fill('#f-dob', '1990-01-01');
  await page.selectOption('#f-gender', 'male');
  await page.fill('#f-email', 'liga@example.com');
  await submitForm(page);
  await expect(page.locator('#f-email-error')).toContainText('already');
});

test('registrar issues a license using the person search', async ({ page }) => {
  await signIn(page, 'registrar');
  await page.goto('registry/license_form.php');
  await pickPerson(page, 'f-civilian_id', 'ozol', 'Marta Ozola');
  await page.selectOption('#f-license_type', 'scooter');
  await page.fill('#f-expires_on', '2000-01-01');
  await submitForm(page);
  await expect(page.locator('#f-expires_on-error')).toContainText('on or after the issue date');
  // The chosen person survives the failed save.
  await expect(page.locator('#f-civilian_id-search')).toHaveValue(/Ozola, Marta/);

  await page.fill('#f-expires_on', '2036-01-01');
  await submitForm(page);
  await expect(flash(page)).toContainText('SCO-');
  await expect(page.locator('h1')).toHaveText('Marta Ozola');
});

test('person search can be used with the keyboard', async ({ page }) => {
  await signIn(page, 'registrar');
  await page.goto('registry/business_form.php');
  const input = page.locator('#f-owner_id-search');
  await input.fill('smi');
  await expect(page.locator('#f-owner_id-list [role=option]').first()).toBeVisible();
  await input.press('ArrowDown');
  await input.press('Enter');
  await expect(input).toHaveValue(/Smith, Jane/);
  expect(await page.locator('#f-owner_id').inputValue()).not.toBe('');
});

test('registrar cannot open police pages', async ({ page }) => {
  await signIn(page, 'registrar');
  expect((await page.goto('police/records.php')).status()).toBe(403);
});

test('officer requests a warrant, sends dispatch, records an offense', async ({ page }) => {
  await signIn(page, 'officer');
  await page.goto(`police/warrant_form.php?civilian_id=${personId}`);
  await expect(page.locator('#f-civilian_id-search')).toHaveValue(/Liga/);
  await page.fill('#f-reason', 'Unpaid fines');
  await submitForm(page);
  await expect(flash(page)).toContainText('sent to a judge');
  const warrant = page.locator('.warrant', { hasText: 'Unpaid fines' });
  await expect(warrant).toHaveAttribute('data-status', 'pending');
  await expect(warrant.locator('button', { hasText: 'Issue' })).toHaveCount(0);

  await page.goto('police/dispatch.php');
  await page.fill('#f-message', 'Test call');
  await page.selectOption('#f-priority', 'urgent');
  await submitForm(page);
  await expect(flash(page)).toContainText('Dispatch sent');
  await page.locator('.feed-dispatch li', { hasText: 'Test call' }).locator('button', { hasText: 'Close' }).click();
  await expect(flash(page)).toContainText('Call closed');

  await page.goto(`police/record_form.php?civilian_id=${personId}`);
  await page.fill('#f-offense', 'Littering');
  await submitForm(page);
  await expect(flash(page)).toContainText('Offense recorded');
});

test('officer cannot forge judge or registrar actions', async ({ page }) => {
  await signIn(page, 'officer');
  // The seeded issued warrant shows a "Mark served" form, which tells us its id.
  await page.goto('police/warrants.php?status=issued');
  const form = page.locator('.warrant', { hasText: 'Failure to appear' }).locator('form');
  const warrantId = await form.locator('input[name=id]').inputValue();
  const token = await csrfToken(page);

  const cancel = await page.request.post('police/warrants.php', { form: { _token: token, id: warrantId, status: 'cancelled' } });
  expect(cancel.status()).toBe(403);

  const del = await page.request.post(`registry/civilian_form.php?id=${personId}`, { form: { _token: token, _action: 'delete', id: personId } });
  expect(del.status()).toBe(403);

  const noToken = await page.request.post('police/dispatch.php', { form: { _action: 'send', message: 'x', priority: 'low' } });
  expect(noToken.status()).toBe(419);
});

test('judge issues the warrant and a court document', async ({ page }) => {
  await signIn(page, 'judge');
  await page.goto('police/warrants.php?status=pending');
  await page.locator('.warrant', { hasText: 'Unpaid fines' }).locator('button', { hasText: 'Issue' }).click();
  await expect(flash(page)).toContainText('is now issued');

  await page.goto(`court/document_form.php?court_file_id=1&civilian_id=${personId}`);
  await expect(page.locator('#f-case_number')).toHaveValue('CF-2022-0001');
  await page.fill('#f-court_name', 'Riga City Court');
  await page.fill('#f-charges', 'Charge one\n\nCharge two\n');
  await submitForm(page);
  await expect(flash(page)).toContainText('issued');
  await expect(page.locator('.charges li')).toHaveCount(2);

  await page.goto('court/file_form.php');
  await page.fill('#f-case_title', 'State vs Test');
  await submitForm(page);
  await expect(flash(page)).toContainText('CF-');
});

test('officer serves the issued warrant', async ({ page }) => {
  await signIn(page, 'officer');
  await page.goto('police/warrants.php?status=issued');
  await page.locator('.warrant', { hasText: 'Unpaid fines' }).locator('button', { hasText: 'Mark served' }).click();
  await expect(flash(page)).toContainText('is now served');
});

test('admin manages users', async ({ page }) => {
  await signIn(page, 'admin');
  await page.goto('admin/user_form.php');
  await page.fill('#f-username', 'liga');
  await page.fill('#f-full_name', 'Liga Test');
  await page.selectOption('#f-role', 'civilian');
  await page.fill('#f-password', 'short');
  await submitForm(page);
  await expect(page.locator('#f-civilian_id-error')).toBeVisible();
  await expect(page.locator('#f-password-error')).toBeVisible();

  await pickPerson(page, 'f-civilian_id', 'liga', 'Liga Test');
  await page.fill('#f-password', 'longenoughpass');
  await submitForm(page);
  await expect(flash(page)).toContainText('can now sign in');

  await page.goto('admin/user_form.php?id=1');
  await page.selectOption('#f-role', 'officer');
  await submitForm(page);
  await expect(page.locator('#f-role-error')).toContainText('own administrator role');
});

test('a civilian sees only their own record', async ({ page }) => {
  await signIn(page, 'liga', 'longenoughpass');
  await expect(page).toHaveURL(/me\.php$/);
  await expect(page.locator('h1')).toContainText('Liga');
  await expect(page.locator('h2', { hasText: 'Warrants' })).toHaveCount(0);
  expect((await page.goto('registry/civilian.php?id=1')).status()).toBe(403);
});

test('activity log shows changes and sign-ins', async ({ page }) => {
  await signIn(page, 'admin');
  await page.goto('admin/activity.php?kind=changes');
  await expect(page.locator('tbody tr', { hasText: 'Requested warrant' })).toHaveCount(1);
  await page.goto('admin/activity.php?kind=signins');
  await expect(page.locator('tbody tr', { hasText: 'liga signed in' })).toHaveCount(1);
});

test('admin deletes a person after confirming', async ({ page }) => {
  await signIn(page, 'admin');
  await page.goto(`registry/civilian_form.php?id=${personId}`);
  await page.locator('.danger-zone button').click();
  await page.locator('dialog .btn-danger').click();
  await expect(page).toHaveURL(/civilians\.php$/);
  await expect(flash(page)).toContainText('removed');
});

test('accounts lock after repeated wrong passwords', async ({ page }) => {
  for (let i = 0; i < 5; i++) await signIn(page, 'registrar', `wrong-${i}`);
  await signIn(page, 'registrar');
  await expect(page.locator('.alert')).toContainText('Too many failed attempts');

  await signIn(page, 'admin');
  await page.goto('admin/activity.php?kind=failed');
  await expect(page.locator('tbody tr', { hasText: 'locked for 10 minutes' })).toHaveCount(1);
});

test('signing out ends the session', async ({ page }) => {
  await signIn(page, 'judge');
  await page.locator('.sidebar-user button[type=submit]').click();
  await expect(page).toHaveURL(/login\.php$/);
  await page.goto('court/files.php');
  await expect(page).toHaveURL(/login\.php$/);
});
