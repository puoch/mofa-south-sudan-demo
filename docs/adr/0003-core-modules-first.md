# 0003. Core modules first, contributed modules only when justified

- Status: Accepted
- Date: 2026-10-24

## Context
The specification asks for maintained, established Drupal capabilities and
minimal custom code. Contributed modules add maintenance and security-update
load, and their PostgreSQL support varies.

## Decision
Phase 1 uses Drupal core only, plus Drush. Candidate contributed modules are
recorded here and added only after a PostgreSQL test in CI:

| Module | Purpose | When |
| --- | --- | --- |
| Pathauto (+ Token) | Automatic URL aliases | Phase 2, if editors need it |
| Metatag | Social and search metadata | Before any public launch |
| TFA or an SSO module | Multifactor login for staff | Before any shared staging site |
| Config Split | Keep dev-only modules out of production | Before any production build |

## Consequences
- Editors set URL aliases by hand for now.
- The development container used to build Phase 1 could not reach
  `packages.drupal.org`, so no contributed module was installed. The Composer
  repository entry is kept so the team can add modules normally.
