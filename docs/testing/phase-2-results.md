# Phase 2 test results

Run on 24 October 2026 against a clean install from `config/sync` plus
`drush mofa:navigation` and `drush mofa:placeholder-content`.

## Structure checks
**122 checks, 0 failed.** Adds: six listing views exist, front page is the
home page, Archived is public, Withdrawn is not, archived items cannot be
republished without review, no contact-form module.

## Browser tests
**122 passed, 0 failed, 32 skipped** (skips are tests that apply only to the
other device project).

| Suite | Result |
| --- | --- |
| axe-core WCAG 2.2 AA on 19 pages, desktop and mobile | Pass |
| Landmarks, one h1, banner, lang, no sideways scroll on 19 pages, desktop and mobile | Pass |
| Reflow at 320px on 19 pages | Pass |
| Every main and footer menu link returns a page (no 404s) | Pass |
| Placeholder notice on content pages | Pass |
| About South Sudan heading, "Discover South Sudan" tagline, seven child pages in order | Pass |
| Dated context notice on History and Government | Pass |
| No population figure published | Pass |
| News keyword search filters results | Pass |
| Archived news only in Archives | Pass |
| Registration page has no form or inputs; button is inactive | Pass |
| Missions labelled as placeholders, no jurisdictions shown | Pass |
| Home page lists the three latest news items from content | Pass |
| Navigation, skip link, noindex, coat of arms alt text, no third-party requests | Pass |

## Not yet tested
- Manual screen reader and keyboard testing (see accessibility.md)
