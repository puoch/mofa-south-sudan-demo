const { test, expect } = require('@playwright/test');
const pages = require('./pages');

const SECTIONS = ['Home', 'Missions', 'About MFA&IC', 'About South Sudan', 'Services', 'News / Press', 'SSD Diaspora', 'Contacts', 'Archives'];

for (const page of pages) {
  test(`${page.name}: landmarks, one h1, demo banner, no sideways scroll`, async ({ page: p }) => {
    await p.goto(page.path);
    await expect(p.locator('header[role="banner"], header.site-header').first()).toBeVisible();
    await expect(p.locator('main#main-content')).toHaveCount(1);
    await expect(p.locator('footer.site-footer')).toHaveCount(1);
    await expect(p.locator('h1')).toHaveCount(1);
    await expect(p.locator('.demo-banner')).toContainText('not an official Government');
    await expect(p.locator('html')).toHaveAttribute('lang', 'en');
    const overflow = await p.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(overflow).toBe(false);
  });
}

test('main navigation lists the nine approved sections in order', async ({ page }) => {
  await page.goto('/styleguide');
  const labels = await page.locator('#site-nav .nav__link').allTextContents();
  expect(labels.map((l) => l.trim())).toEqual(SECTIONS);
});

test('skip link moves focus to the main content', async ({ page }) => {
  await page.goto('/styleguide');
  await page.keyboard.press('Tab');
  const skip = page.locator('.skip-link');
  await expect(skip).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/#main-content$/);
});

test('pages are excluded from search engines', async ({ page }) => {
  await page.goto('/styleguide');
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
});

test('the coat of arms is a labelled placeholder, not official artwork', async ({ page }) => {
  await page.goto('/styleguide');
  await expect(page.locator('.site-identity__arms')).toHaveAttribute('alt', /Placeholder/);
});

test('the demonstration registration button cannot be activated', async ({ page }) => {
  await page.goto('/styleguide');
  const inactive = page.locator('.button--inactive');
  await expect(inactive.first()).toBeVisible();
  expect(await inactive.first().evaluate((el) => el.tagName)).toBe('SPAN');
  await expect(page.locator('form[action*="regist"]')).toHaveCount(0);
});

test('no third-party requests are made', async ({ page }) => {
  const external = [];
  page.on('request', (r) => {
    const host = new URL(r.url()).hostname;
    if (!['127.0.0.1', 'localhost'].includes(host) && !r.url().startsWith('data:')) external.push(r.url());
  });
  await page.goto('/styleguide/layouts/home');
  await page.waitForLoadState('networkidle');
  expect(external).toEqual([]);
});
