// Phase 2: public pages, placeholder labelling and listings.
const { test, expect } = require('@playwright/test');

test.describe('site content', () => {
  test.skip(({ isMobile }) => isMobile, 'Content checks run once');

  test('every main and footer menu link opens a page', async ({ page, request }) => {
    await page.goto('/');
    const hrefs = await page.locator('#site-nav a, .site-footer a').evaluateAll((links) =>
      [...new Set(links.map((a) => a.getAttribute('href')))]);
    expect(hrefs.length).toBeGreaterThan(40);
    const broken = [];
    for (const href of hrefs) {
      const res = await request.get(href);
      if (res.status() !== 200) broken.push(`${href} -> ${res.status()}`);
    }
    expect(broken).toEqual([]);
  });

  test('content pages show the placeholder notice', async ({ page }) => {
    for (const path of ['/missions', '/about-south-sudan', '/about-south-sudan/basic-information', '/news/placeholder-statement-1', '/missions/placeholder-mission-b']) {
      await page.goto(path);
      await expect(page.locator('.placeholder-notice'), path).toBeVisible();
    }
  });

  test('About South Sudan gateway uses the approved heading, tagline and seven child pages', async ({ page }) => {
    await page.goto('/about-south-sudan');
    await expect(page.locator('h1')).toHaveText('About South Sudan');
    await expect(page.locator('.page-header__lead')).toHaveText('Discover South Sudan');
    const tiles = page.locator('.link-tiles__title');
    await expect(tiles).toHaveText([
      'Basic Information', 'History and Government', 'Geography and Natural Heritage',
      'People and Culture', 'Economy and Investment', 'Foreign Relations', 'National Symbols',
    ]);
  });

  test('time-sensitive information sits in a dated notice', async ({ page }) => {
    await page.goto('/about-south-sudan/history-and-government');
    await expect(page.locator('.context-notice time')).toHaveAttribute('datetime', '2026-10-24');
  });

  test('no population figure is published', async ({ page }) => {
    await page.goto('/about-south-sudan/basic-information');
    await expect(page.locator('main')).not.toContainText(/\d[\d.,]*\s*million/i);
  });

  test('news search filters results', async ({ page }) => {
    await page.goto('/news');
    const all = await page.locator('.listing__item').count();
    expect(all).toBeGreaterThan(2);
    await page.getByLabel('Search').fill('statement');
    await page.getByRole('button', { name: 'Apply' }).click();
    await expect(page.locator('.listing__item')).toHaveCount(1);
    await expect(page.locator('.listing__item')).toContainText('Placeholder statement headline');
  });

  test('archived news is in the Archives, not the current news list', async ({ page }) => {
    await page.goto('/news');
    await expect(page.locator('main')).not.toContainText('archived news headline');
    await page.goto('/archives/news');
    await expect(page.locator('main')).toContainText('Placeholder archived news headline');
  });

  test('the registration page collects nothing', async ({ page }) => {
    await page.goto('/diaspora/registration');
    await expect(page.locator('main form, main input, main select, main textarea')).toHaveCount(0);
    await expect(page.locator('.button--inactive')).toContainText('Register');
    expect(await page.locator('.button--inactive').evaluate((el) => el.tagName)).toBe('SPAN');
  });

  test('the help and enquiries page collects nothing and points to emergency services', async ({ page }) => {
    await page.goto('/diaspora/assistance');
    await expect(page.locator('main')).toContainText('This is not an emergency service');
    await expect(page.locator('main')).toContainText('contact the local emergency services');
    await expect(page.locator('main form, main input, main select, main textarea')).toHaveCount(0);
    await expect(page.locator('.button--inactive')).toContainText('Ask for help');
  });

  test('missions are labelled as placeholders and show no jurisdictions', async ({ page }) => {
    await page.goto('/missions/directory');
    await expect(page.locator('.listing__header')).toContainText('placeholders');
    await page.goto('/missions/placeholder-mission-a');
    await expect(page.locator('main')).toContainText('Unverified placeholder');
    await expect(page.locator('.field--name-field-countries-served')).toHaveCount(0);
  });

  test('home page lists the latest news from content', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.listing__item')).toHaveCount(3);
    await expect(page.locator('.listing__item').first()).toContainText('Placeholder press release headline');
  });
});
