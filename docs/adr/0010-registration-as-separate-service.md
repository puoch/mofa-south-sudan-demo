# 0010. Diaspora registration as a separately protected service

- Status: Prototype built in Phase 3 with synthetic data only. Approval for real use is still open.
- Date: 2026-10-24

## Context
Registration would store passport details. The specification requires that
external maintainers have no routine access and that mission staff see only
their own jurisdiction. Drupal administrators and anyone with server or
database access to this site can reach everything stored in it.

## Proposal
When authorized, build registration as a separate service with its own
database (with row-level security), encryption keys and staff login. This
Drupal site would only link to it and pass no personal data.

## Status in this repository
Phase 2 added an informational page and an inactive entry point. Phase 3
builds the service in `registration-service/` as a development prototype that
accepts synthetic data only (ADR 0013 to 0015). Staff access, routing to
missions, row-level security and anything production-facing are not built.
