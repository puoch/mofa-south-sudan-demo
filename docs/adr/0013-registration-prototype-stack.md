# 0013. Registration prototype: Symfony service with its own database

- Status: Accepted for the Phase 3 prototype
- Date: 2026-10-24

## Context
ADR 0010 proposes keeping registration out of Drupal. The prototype needs a
stack the same PHP team can maintain, that runs on PostgreSQL and that adds
few dependencies.

## Decision
- `registration-service/` is a Symfony 7.4 LTS application (supported until
  November 2029; Symfony 8 needs PHP 8.4, and the project uses PHP 8.3).
- It has its own PostgreSQL database and database role (`mofa_registry`).
  Drupal has no credentials for it and passes it no data: the Drupal page only
  links to it, and only when `MOFA_REGISTRATION_PROTOTYPE_URL` is set.
- Dependencies are Symfony components, Twig and Doctrine ORM with migrations.
  No JavaScript is used; forms are rendered on the server.
- Styles reuse the website theme's `tokens.css`, `base.css` and `forms.css`
  as exact copies. CI fails if the copies drift.
- Passport details and date of birth are stored in a separate
  `registration_sensitive` table so later phases can grant staff access to
  contact details without those columns.

## Consequences
Two applications to host and patch. In local development both databases sit
on one PostgreSQL server; in any real deployment they must be separate
instances with separate network rules.
