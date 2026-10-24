# 0001. Drupal 11 on PostgreSQL 16

- Status: Accepted (Phase 1)
- Date: 2026-10-24

## Context
The specification calls for a supported Drupal version with PHP and a
PostgreSQL database.

## Decision
Use Drupal 11.4 (`drupal/recommended-project`) with PHP 8.3 and PostgreSQL 16,
the minimum PostgreSQL version Drupal 11 supports. Confirm the supported
Drupal minor version again before any launch.

## Consequences
- Core is well tested on PostgreSQL; many contributed modules are mainly
  tested on MySQL/MariaDB, so each contributed module must be tested on
  PostgreSQL before adoption (see ADR 0003).
- `pg_trgm` is enabled in the development database for future search needs.
