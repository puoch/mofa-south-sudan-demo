# 0012. Home page assembled from content

- Status: Accepted (Phase 2)
- Date: 2026-10-24

## Decision
The front page (`/home`, `mofa_core.home` route) is assembled by a small
controller rather than a hand-edited page:

- Hero tagline: the site slogan (Configuration > Basic site settings);
  placeholder text until a slogan is approved.
- Ministry announcements: the `news` view's "Latest" block (three newest
  published items).
- Service pathways: fixed links to Services, Diaspora and Missions.
- Discover South Sudan: the opening paragraph of the About South Sudan
  gateway page.

## Consequences
- Publishing news or editing the gateway updates the home page with no extra
  step.
- The hero introduction text is still fixed placeholder copy. When approved
  text exists, make it editable (for example a block or a field on a Home
  page) rather than editing the controller.
