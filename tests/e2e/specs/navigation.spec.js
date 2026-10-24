const { test, expect } = require('@playwright/test');

test.describe('desktop navigation', () => {
  test.skip(({ isMobile }) => isMobile, 'Desktop behaviour');

  test('section buttons open one submenu at a time and Escape closes it', async ({ page }) => {
    await page.goto('/styleguide');
    const toggles = page.locator('[data-nav-toggle]');
    const first = toggles.nth(0);
    const second = toggles.nth(1);
    await expect(first).toHaveAttribute('aria-expanded', 'false');
    await first.click();
    await expect(first).toHaveAttribute('aria-expanded', 'true');
    const firstSub = page.locator('#' + (await first.getAttribute('aria-controls')));
    await expect(firstSub).toBeVisible();
    await second.click();
    await expect(first).toHaveAttribute('aria-expanded', 'false');
    await expect(firstSub).toBeHidden();
    await page.keyboard.press('Escape');
    await expect(second).toHaveAttribute('aria-expanded', 'false');
    await expect(second).toBeFocused();
  });

  test('submenu links are reachable by keyboard', async ({ page }) => {
    await page.goto('/styleguide');
    const toggle = page.locator('[data-nav-toggle]').nth(2);
    await toggle.focus();
    await page.keyboard.press('Enter');
    await page.keyboard.press('Tab');
    const focused = await page.evaluate(() => document.activeElement.className);
    expect(focused).toContain('nav__sublink');
  });
});

test.describe('mobile navigation', () => {
  test.skip(({ isMobile }) => !isMobile, 'Mobile behaviour');

  test('Menu button shows and hides the navigation', async ({ page }) => {
    await page.goto('/styleguide');
    const menu = page.locator('.site-header__menu-toggle');
    const nav = page.locator('#site-nav');
    await expect(menu).toBeVisible();
    await expect(nav).toBeHidden();
    await menu.click();
    await expect(menu).toHaveAttribute('aria-expanded', 'true');
    await expect(nav).toBeVisible();
    await menu.click();
    await expect(nav).toBeHidden();
  });
});

test('navigation works without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('/styleguide');
  await expect(page.locator('#site-nav')).toBeVisible();
  await expect(page.locator('.nav__sublink').first()).toBeVisible();
  await context.close();
});
