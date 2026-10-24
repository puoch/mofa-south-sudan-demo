# Phase 2 report: public website foundation and placeholder content

Date: 24 October 2026. Authorized by the project owner on 24 October 2026
after the Phase 1 review. Development stops here pending further
authorization.

## Delivered

1. **All nine sections live with placeholder content.** Home, Missions,
   About MFA&IC, About South Sudan, Services, News / Press, SSD Diaspora,
   Contacts and Archives, plus every child page in the navigation and the
   footer pages (About this demonstration, Accessibility, Privacy notice).
   Every menu link resolves; a test enforces it.
2. **About South Sudan gateway.** Heading "About South Sudan", tagline
   "Discover South Sudan", placeholder introduction and the seven approved
   child pages. History and Government carries a dated context notice.
   Internal notes on each page record the pending decisions from the
   research report (population, language wording, administrative structure,
   sensitive history, current transition).
3. **Listings.** Searchable news archive with a category filter, category
   pages for news and services, mission directory, leadership, publications,
   archived news and historical announcements.
4. **Home page from content.** Latest news, service pathways and the About
   South Sudan introduction update automatically.
5. **SSD Diaspora.** Landing page, guidance pages and a registration page
   that explains what is planned, with an inactive, clearly labelled button.
   No form, no inputs, nothing collected.
6. **Workflow refinement.** Archived items stay public in the Archives;
   a new Withdrawn state takes items off the site.
7. **Labelling.** All 51 items are synthetic and marked "Unverified
   placeholder content". No real officeholders, missions, jurisdictions,
   contact details, statistics, maps or policies.
8. **Tests.** 122 structure checks and 122 browser tests pass (axe-core
   WCAG 2.2 AA on 19 pages, desktop and mobile).
9. **Docs.** Editor guide, ADRs 0011 and 0012, updated content model,
   workflow, approvals and README.

## Not done (not authorized)
Real content, registration, contact forms, staff records access, hosting,
production deployment.

## Known limitations
- Placeholder items are rebuilt by `drush mofa:placeholder-content`, which
  overwrites edits to them.
- The home page introduction is fixed placeholder text (ADR 0012).
- Contributed modules (Pathauto, Metatag, two-factor login) remain deferred.
- Manual screen reader testing has not been done.

## Needed before further work
See `approvals-and-blockers.md`: sitemap confirmation, official wordmark and
colour specification, vector coat of arms, approved content, and the
government decisions listed there.
