// WCAG 1.4.10 Reflow: content works at 320 CSS pixels wide without
// horizontal scrolling (equivalent to 400% zoom on a 1280px screen).
const { test, expect } = require('@playwright/test');
const pages = require('./pages');

test.describe('reflow at 320px', () => {
  test.skip(({ isMobile }) => isMobile, 'Run once');
  for (const page of pages) {
    test(`${page.name} reflows at 320px`, async ({ page: p }) => {
      await p.setViewportSize({ width: 320, height: 640 });
      await p.goto(page.path);
      const overflow = await p.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
      expect(overflow).toBe(false);
    });
  }
});
