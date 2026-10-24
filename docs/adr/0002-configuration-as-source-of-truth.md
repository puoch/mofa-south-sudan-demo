# 0002. Exported configuration is the source of truth

- Status: Accepted
- Date: 2026-10-24

## Decision
All site structure (content types, fields, workflow, roles, blocks, theme
settings) lives in `config/sync` and is applied with `drush config:import` or
`drush site:install --existing-config`. CI installs a fresh site from
configuration on every change and fails if the database and `config/sync`
differ.

## Consequences
- Changes made through the admin UI must be exported and committed.
- Environment-specific values (database, hash salt, trusted hosts) are never
  in configuration; they come from environment variables (`settings.php`).
- Menu links and other content are not configuration; see ADR 0009.
