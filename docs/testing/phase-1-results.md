# Phase 1 test results

Run on 24 October 2026 against a clean install from `config/sync`
(Drupal 11.4.11, PHP 8.3.6, PostgreSQL 16).

## Structure checks (`tests/drush/structure-check.php`)
**110 checks, 0 failed.** Covers: 8 content types with all editorial fields,
placeholder default on, translation enabled, mission and service status
defaults, 5 workflow states, no shortcut from draft or review to published,
separation of authoring/reviewing/publishing, no admin permissions on
editorial roles, anonymous permissions limited, visitor registration
disabled, English only, no registration content type or form module.

## Browser tests (`tests/e2e`)
**47 passed, 0 failed, 9 skipped** (the skipped tests are desktop-only or
mobile-only tests that do not apply to the other device project).

| Suite | Desktop | Mobile |
| --- | --- | --- |
| axe-core WCAG 2.2 AA, 6 pages | Pass | Pass |
| Structure (landmarks, one h1, banner, lang, no sideways scroll), 6 pages | Pass | Pass |
| Nine sections in approved order | Pass | Pass |
| Skip link | Pass | Pass |
| noindex on every page | Pass | Pass |
| Coat of arms labelled as placeholder | Pass | Pass |
| Demonstration registration button cannot be activated | Pass | Pass |
| No third-party requests | Pass | Pass |
| Desktop submenu behaviour and keyboard access | Pass | n/a |
| Mobile Menu button | n/a | Pass |
| Navigation without JavaScript | Pass | Pass |
| Reflow at 320px, 6 pages | Pass | n/a |

## Issues found and fixed during testing
- The component library showed a second `h1` inside the hero example; the
  hero now takes a heading level.
- Login form fields overflowed at 320px; theme form styles added.
- The anonymous role had a text-format permission from a core recipe;
  removed.

## Not yet tested
- Manual screen reader and keyboard testing (see accessibility.md)
- Real content pages (Phase 2)
- CI on GitHub Actions (runs on the first push)
