# Phase 1 report: setup, design system and content architecture

Date: 24 October 2026. Scope as authorized on 24 October 2026 (Phase 1 only).

## Delivered

1. **Development environment.** Drupal 11.4.11, PHP 8.3, PostgreSQL 16;
   DDEV configuration; environment-variable settings with no secrets;
   one-command install from configuration (`scripts/install-from-config.sh`).
2. **Project structure and configuration management.** All structure is in
   `config/sync`; a clean install from it matches exactly.
3. **Information architecture and navigation.** Nine top-level sections in
   the approved order with 32 child pages, kept in `navigation.yml`.
   Accessible disclosure navigation on desktop, Menu button on phones,
   works without JavaScript.
4. **Design system and theme.** Tokens with a clearly temporary dark blue,
   self-hosted fonts, logical CSS for later right-to-left, placeholder coat of
   arms beside a typographic MFA&IC wordmark, site-wide demonstration banner.
5. **Reusable layouts and components.** 13 single-directory components and
   home, gateway, listing and detail layouts, all viewable at `/styleguide`.
6. **Content management structure.** Eight content types, five vocabularies,
   image and document media, shared editorial fields, placeholder flag on by
   default, five-state editorial workflow, four editorial roles with
   separated duties.
7. **Arabic readiness.** All content translatable; English only enabled.
8. **Accessibility.** WCAG 2.2 AA patterns built in; automated tests pass.
9. **Tests and CI.** 110 structure checks and 47 browser tests pass locally;
   GitHub Actions workflow included.
10. **Documentation.** Architecture, ten decision records, content model,
    design system, testing guide and results, research-report notes,
    approvals and blockers.

## Not done in Phase 1 (by design)
- No placeholder pages for the nine sections yet (Phase 2). Menu links
  currently lead to "Page not found".
- No real content, no registration, no contact forms, no hosting.

## Known limitations
- Contributed modules (Pathauto, Metatag, multifactor login) could not be
  downloaded in the build environment and are deferred (ADR 0003).
- The component library is public in this development build so it can be
  reviewed; it must be removed before any production use.
- Manual screen reader testing has not been done yet.

## Proposed Phase 2 work (after review)
Placeholder pages for all nine sections using the layouts; About South Sudan
gateway and seven child pages with the approved concept; listing views for
news, missions, services, publications and archives; searchable news
archive; diaspora landing page with an inactive registration entry point;
editor guide.
