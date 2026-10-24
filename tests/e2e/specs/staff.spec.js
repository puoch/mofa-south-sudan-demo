// Staff console of the registration prototype: sign-in with an
// authenticator code, accessibility of each screen, masking and scope.
// Needs REGISTRY_URL plus REGISTRY_DEMO_PASSWORD and
// REGISTRY_DEMO_TOTP_SECRET matching `registry:seed-staff --set=desktop
// --set=mobile`; skipped otherwise.
const crypto = require('crypto');
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const base = process.env.REGISTRY_URL;
const password = process.env.REGISTRY_DEMO_PASSWORD;
const secret = process.env.REGISTRY_DEMO_TOTP_SECRET;
test.skip(!base || !password || !secret, 'Staff console environment not set');

const totp = (b32) => {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const c of b32.toUpperCase()) bits += alphabet.indexOf(c).toString(2).padStart(5, '0');
  const key = Buffer.from(bits.match(/.{8}/g).map((b) => parseInt(b, 2)));
  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const hmac = crypto.createHmac('sha1', key).update(counter).digest();
  const offset = hmac[19] & 0xf;
  return String((hmac.readUInt32BE(offset) & 0x7fffffff) % 1e6).padStart(6, '0');
};

const axe = async (page) => {
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  return results.violations.map((v) => `${v.id}: ${v.help} (${v.nodes.length})`);
};

const signIn = async (page, account) => {
  await page.goto(`${base}/staff/login`);
  await page.getByLabel('Email address').fill(account);
  await page.getByLabel('Password').fill(password);
  await page.getByLabel('Authenticator code').fill(totp(secret));
  await page.getByRole('button', { name: 'Sign in' }).click();
};

test('sign-in page is accessible and a wrong code is refused', async ({ page }) => {
  await page.goto(`${base}/staff/login`);
  expect(await axe(page)).toEqual([]);
  await page.getByLabel('Email address').fill('nobody@example.org');
  await page.getByLabel('Password').fill('wrong-password');
  await page.getByLabel('Authenticator code').fill('000000');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page.getByRole('alert')).toContainText('not correct');
  expect(await axe(page)).toEqual([]);
});

test('mission staff see only their mission, masked until revealed', async ({ page }, info) => {
  await signIn(page, `mission.a.${info.project.name}@example.org`);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Records');
  expect(await axe(page)).toEqual([]);
  const first = page.locator('tbody a').first();
  if (await first.count() === 0) test.skip(true, 'No records routed to DEMO-A');
  await first.click();
  await expect(page.locator('.watermark')).toContainText(`mission.a.${info.project.name}@example.org`);
  expect(await axe(page)).toEqual([]);
  const reveal = page.getByRole('button', { name: 'Reveal document numbers' });
  if (await reveal.count()) {
    await expect(page.locator('main')).toContainText('•');
    await reveal.click();
    await expect(page.locator('main')).toContainText('The reveal has been logged');
    expect(await axe(page)).toEqual([]);
  }
});

test('oversight officers read the audit log', async ({ page }, info) => {
  await signIn(page, `hq.oversight.${info.project.name}@example.org`);
  await page.getByRole('link', { name: 'Audit log' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Audit log');
  await expect(page.locator('table')).toContainText('staff.login');
  expect(await axe(page)).toEqual([]);
});

test('assignment officers can reassign from the HQ queue', async ({ page }, info) => {
  await signIn(page, `hq.assignment.${info.project.name}@example.org`);
  await page.getByLabel('Assigned to').selectOption('hq_queue');
  await page.getByRole('button', { name: 'Filter' }).click();
  expect(await axe(page)).toEqual([]);
  const first = page.locator('tbody a').first();
  if (await first.count() === 0) test.skip(true, 'Queue is empty');
  await first.click();
  await expect(page.getByRole('button', { name: 'Reveal document numbers' })).toHaveCount(0);
  await page.getByRole('button', { name: 'Reassign record' }).click();
  await expect(page.getByRole('alert')).toContainText('Give a reason');
  expect(await axe(page)).toEqual([]);
});

test('account administrators see accounts but no records', async ({ page }, info) => {
  await signIn(page, `accounts.${info.project.name}@example.org`);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Staff accounts');
  expect(await axe(page)).toEqual([]);
  const response = await page.goto(`${base}/staff/records`);
  expect(response.status()).toBe(403);
});

test('staff console reflows at 320 CSS pixels', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 800 });
  await page.goto(`${base}/staff/login`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(0);
});
