# MoFA WEBSITE (development demonstration)

A proposed website for the Ministry of Foreign Affairs and International
Cooperation (MFA&IC) of the Republic of South Sudan, built with Drupal 11 and
PostgreSQL.

> **Status: development only.** This is not an official Government of the
> Republic of South Sudan website. It is not deployed, not government-approved
> and not connected to any government system. All content is placeholder and
> unverified. No personal information is collected.

## What is in this repository

| Path | What it is |
| --- | --- |
| `config/sync/` | Exported Drupal configuration. The source of truth for content types, workflow, roles, theme settings and blocks. |
| `web/themes/custom/mofa_theme/` | Responsive, accessible public theme: design tokens, layouts and single-directory components. |
| `web/modules/custom/mofa_core/` | Home page, navigation (`navigation.yml`, `drush mofa:navigation`) and synthetic placeholder content (`placeholder_content.yml`, `drush mofa:placeholder-content`). |
| `web/modules/custom/mofa_styleguide/` | Development-only component library at `/styleguide`. Disable before any production use. |
| `web/sites/default/settings.php` | Settings read from environment variables. No secrets. |
| `registration-service/` | Phase 3 diaspora registration prototype: a separate Symfony app with its own database. Synthetic data only. See its README. |
| `tests/drush/` | Structure checks for the content model, workflow and permissions. |
| `tests/e2e/` | Playwright end-to-end, accessibility (axe-core) and reflow tests. |
| `docs/` | Architecture, decisions, content model, design system, testing and open approvals. |

## Run it locally

### With DDEV (recommended)

Requires Docker and [DDEV](https://ddev.com).

```bash
ddev start
ddev composer install
ddev exec ./scripts/install-from-config.sh
ddev launch /styleguide
```

### Without DDEV

Requires PHP 8.3 (with `pdo_pgsql`, `gd`, `intl`), Composer 2 and PostgreSQL 16.

```bash
composer install
createuser mofa --pwprompt
createdb mofa --owner=mofa --encoding=UTF8 --template=template0
cp .env.example .env            # then fill in DB_PASSWORD and DRUPAL_HASH_SALT
set -a; . ./.env; set +a
./scripts/install-from-config.sh
cd web && php -S 127.0.0.1:8080 .ht.router.php
```

Open http://127.0.0.1:8080/. The component library is at `/styleguide`.

## Everyday commands

```bash
vendor/bin/drush config:export      # after changing configuration in the UI
vendor/bin/drush config:import      # apply configuration from config/sync
vendor/bin/drush mofa:navigation    # create or update the menus from navigation.yml
vendor/bin/drush mofa:placeholder-content  # create or update the synthetic placeholder content
vendor/bin/drush php:script tests/drush/structure-check.php
cd tests/e2e && npm ci && npx playwright test
```

## Project phases

| Phase | Scope | Status |
| --- | --- | --- |
| 1 | Setup, design system, content architecture | Complete |
| 2 | Public website foundation with placeholder pages | Complete |
| 3 | Diaspora registration prototype, synthetic data only | Complete, awaiting review |
| Later | Staff and mission access, real content, hosting | Not authorized |

See [docs/approvals-and-blockers.md](docs/approvals-and-blockers.md) for what
is still pending, [docs/phase-1-report.md](docs/phase-1-report.md) and
[docs/phase-2-report.md](docs/phase-2-report.md) and
[docs/phase-3-report.md](docs/phase-3-report.md) for phase summaries, and
[docs/guides/editor-guide.md](docs/guides/editor-guide.md) for editors.

## Rules for contributors

- Use synthetic or placeholder data only. Never use real personal information.
- Do not invent government facts, officeholders, jurisdictions, statistics,
  maps or colour specifications. Mark anything unverified as a placeholder.
- Never commit secrets. Configuration comes from environment variables.
- Export configuration and commit it with the code change that needs it.
