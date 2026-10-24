// Registration prototype: accessibility, reflow and the synthetic-only
// guards, run in a real browser. Needs the service running at REGISTRY_URL;
// skipped otherwise.
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const base = process.env.REGISTRY_URL;
test.skip(!base, 'REGISTRY_URL is not set');

const axe = async (page) => {
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  return results.violations.map((v) => `${v.id}: ${v.help} (${v.nodes.length})`);
};

const fillSynthetic = async (page, overrides = {}) => {
  const values = {
    'Full name': 'Amani Example',
    'Date of birth': '1990-05-17',
    'Email address': `amani${Date.now()}@example.org`,
    'Phone number': '+15550100123',
    'City of residence': 'Sample City',
    'Passport number': `SYN${String(Math.floor(Math.random() * 1e6)).padStart(6, '0')}`,
    'Passport expiry date': '2031-02-01',
    ...overrides,
  };
  for (const [label, value] of Object.entries(values)) {
    await page.getByLabel(label, { exact: true }).fill(value);
  }
  for (const label of ['Nationality', 'Country of residence', 'Passport issuing country']) {
    await page.getByLabel(label, { exact: true }).selectOption('SS');
  }
  await page.getByLabel(/every detail I entered is made up/).check();
  await page.getByLabel('I have read the draft privacy notice.').check();
};

test('form page has no detectable WCAG 2.2 AA violations', async ({ page }) => {
  await page.goto(`${base}/register`);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Register as a member of the diaspora');
  await expect(page.getByRole('region', { name: 'Demonstration notice' })).toContainText('Do not enter real');
  expect(await axe(page)).toEqual([]);
});

test('errors are summarised, linked and accessible', async ({ page }) => {
  await page.goto(`${base}/register`);
  await fillSynthetic(page, { 'Passport number': 'R00123456', 'Email address': 'someone@gmail.com' });
  await page.getByRole('button', { name: 'Submit registration' }).click();
  const summary = page.getByRole('alert');
  await expect(summary).toContainText('only made-up passport numbers');
  await expect(summary).toContainText('only made-up addresses');
  await expect(page).toHaveTitle(/^Error:/);
  await summary.getByRole('link', { name: /passport numbers/ }).click();
  await expect(page.getByLabel('Passport number', { exact: true })).toBeFocused();
  expect(await axe(page)).toEqual([]);
});

test('a synthetic registration returns a reference number once', async ({ page }) => {
  await page.goto(`${base}/register`);
  await fillSynthetic(page);
  await page.getByRole('button', { name: 'Submit registration' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Registration received');
  await expect(page.locator('.reference')).toHaveText(/^SSD(-[0-9A-HJKMNP-TV-Z]{4}){4}$/);
  expect(page.url()).not.toContain('SSD-');
  expect(await axe(page)).toEqual([]);
  await page.reload();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Register as a member of the diaspora');
});

test('draft privacy notice is accessible and marked as a placeholder', async ({ page }) => {
  await page.goto(`${base}/privacy`);
  await expect(page.locator('main')).toContainText('Placeholder');
  expect(await axe(page)).toEqual([]);
});

test('form reflows at 320 CSS pixels without horizontal scrolling', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 800 });
  await page.goto(`${base}/register`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(0);
});
