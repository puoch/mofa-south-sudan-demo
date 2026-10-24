// Help and enquiries prototype: accessibility, reflow, routing display and
// the emergency notice, run in a real browser. Needs the registration service
// running at REGISTRY_URL; skipped otherwise.
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

const fillSynthetic = async (page, country, overrides = {}) => {
  await page.getByLabel('I am in distress and need help').check();
  const values = {
    'Full name': 'Amani Example',
    'Email address': 'amani@example.org',
    'Phone number': '+15550100123',
    'Describe your situation or enquiry': 'Synthetic test: my made-up wallet was stolen and I need advice.',
    'City or town': 'Sample City',
    'More about where you are (optional)': 'Near the made-up central bus station',
    ...overrides,
  };
  for (const [label, value] of Object.entries(values)) {
    await page.getByLabel(label, { exact: true }).fill(value);
  }
  await page.getByLabel('Country you are in now', { exact: true }).selectOption(country);
  await page.getByLabel(/every detail I entered is made up/).check();
  await page.getByLabel(/no one will respond to this request/).check();
};

test('form says it is not an emergency service and has no WCAG 2.2 AA violations', async ({ page }) => {
  await page.goto(`${base}/assistance`);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Ask for help or make an enquiry');
  await expect(page.getByRole('note', { name: 'This is not an emergency service' })).toContainText('contact the local emergency services');
  expect(await axe(page)).toEqual([]);
});

test('errors are summarised, linked and accessible', async ({ page }) => {
  await page.goto(`${base}/assistance`);
  await page.getByRole('button', { name: 'Submit request' }).click();
  const summary = page.getByRole('alert');
  await expect(summary).toContainText('Select whether you need help or have an enquiry.');
  await expect(page).toHaveTitle(/^Error:/);
  await summary.getByRole('link', { name: /need help or have an enquiry/ }).click();
  await expect(page.getByLabel('I am in distress and need help')).toBeFocused();
  expect(await axe(page)).toEqual([]);
});

// Needs the synthetic missions from registry:seed-missions: Kenya is served
// by Demonstration Mission A, France has no rule.
test('a request goes to headquarters and the mission serving the country, shown once', async ({ page }) => {
  await page.goto(`${base}/assistance`);
  await fillSynthetic(page, 'KE');
  await page.getByRole('button', { name: 'Submit request' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Request received');
  await expect(page.locator('.reference')).toHaveText(/^SSD(-[0-9A-HJKMNP-TV-Z]{4}){4}$/);
  await expect(page.locator('.routing')).toContainText('Received (every request goes to headquarters)');
  await expect(page.locator('.routing')).toContainText('Received by Demonstration Mission A (synthetic)');
  await expect(page.getByRole('note', { name: 'No one will respond' })).toBeVisible();
  expect(page.url()).not.toContain('SSD-');
  expect(await axe(page)).toEqual([]);
  await page.reload();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Ask for help or make an enquiry');
});

test('a request from a country no mission serves stays with headquarters', async ({ page }) => {
  await page.goto(`${base}/assistance`);
  await fillSynthetic(page, 'FR');
  await page.getByRole('button', { name: 'Submit request' }).click();
  await expect(page.locator('.routing')).toContainText('no single approved mission serves this country');
});

test('form reflows at 320 CSS pixels without horizontal scrolling', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 800 });
  await page.goto(`${base}/assistance`);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(0);
});
