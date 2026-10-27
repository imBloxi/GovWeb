// Paths are relative (no leading slash) so tests also work when GovWeb lives in a subfolder.
const { expect } = require('@playwright/test');

const PASSWORD = 'password';
const PHP_ERROR = /(Warning|Fatal error|Deprecated|Notice|Parse error)\b.{0,300} on line \d+|Uncaught \w+/;

async function signIn(page, username, password = PASSWORD) {
  await page.goto('login.php');
  await page.fill('#username', username);
  await page.fill('#password', password);
  await page.click('button[type=submit]');
  await page.waitForLoadState();
}

function flash(page) {
  return page.locator('.alert').first();
}

async function submitForm(page) {
  await page.click('form.form-grid button[type=submit]');
  await page.waitForLoadState();
}

/** Choose someone in a person picker by typing part of their name. */
async function pickPerson(page, fieldId, query, expectedName) {
  const input = page.locator(`#${fieldId}-search`);
  await input.fill(query);
  const option = page.locator(`#${fieldId}-list [role=option]`, { hasText: expectedName }).first();
  await expect(option).toBeVisible();
  await option.click();
}

/** The CSRF token from the current page, for hand-made requests. */
function csrfToken(page) {
  return page.locator('input[name=_token]').first().inputValue();
}

module.exports = { PASSWORD, PHP_ERROR, signIn, flash, submitForm, pickPerson, csrfToken };
