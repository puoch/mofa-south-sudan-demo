// Automated WCAG 2.2 A/AA checks with axe-core. Automated tools find only
// part of the possible issues; see docs/testing/accessibility.md for the
// manual checks that must accompany these.
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const pages = require('./pages');

for (const page of pages) {
  test(`${page.name} has no detectable WCAG 2.2 AA violations`, async ({ page: p }) => {
    await p.goto(page.path);
    const results = await new AxeBuilder({ page: p })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
      .analyze();
    const summary = results.violations.map((v) => `${v.id}: ${v.help} (${v.nodes.length})`);
    expect(summary).toEqual([]);
  });
}
