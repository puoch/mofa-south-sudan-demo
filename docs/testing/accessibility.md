# Accessibility testing

Target: WCAG 2.2 Level AA.

## Automated (runs in CI)

`tests/e2e` (Playwright, desktop and mobile):

- **axe-core** with WCAG 2.0/2.1/2.2 A and AA rules on every covered page
- One `h1`, landmarks, `lang="en"`, skip link moves focus to the main content
- No horizontal scrolling at desktop, phone and 320px widths (WCAG 1.4.10)
- Navigation: disclosure buttons, one submenu open at a time, Escape closes
  and returns focus, submenu links reachable by keyboard, works without JS
- No third-party requests

Run locally:

```bash
cd web && php -S 127.0.0.1:8080 .ht.router.php &
cd tests/e2e && npm ci && npx playwright install chromium && npx playwright test
```

Automated tools find only part of WCAG failures. The manual checks below are
required before any page is approved.

## Manual checks (each new template or component)

| Check | How |
| --- | --- |
| Keyboard only | Tab through the whole page. Every control reachable, order logical, focus always visible, no traps. |
| Screen reader | NVDA + Firefox or Chrome on Windows, VoiceOver + Safari on macOS and iOS, TalkBack on Android. Headings list, landmarks list, link names make sense out of context. |
| Zoom | 200% text zoom and 400% page zoom: nothing cut off or overlapping. |
| Text spacing | Apply the WCAG 1.4.12 bookmarklet: no loss of content. |
| Colour | Information never conveyed by colour alone; check contrast of any new colour (4.5:1 text, 3:1 UI). |
| Reduced motion | Enable "reduce motion" in the OS: no essential animation. |
| Forced colours | Windows high-contrast mode: focus and borders still visible. |
| Images | Meaningful images have descriptive alt text; decorative images have empty alt. Coat of arms, flag and maps always need alt text. |
| Forms (Phase 2+) | Visible labels, errors identified in text and linked to fields, no time limits. |

## Recommendation
Commission an independent accessibility audit, including testing with
disabled users, before any public launch.
