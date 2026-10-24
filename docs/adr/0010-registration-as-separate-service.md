# 0010. Diaspora registration as a separately protected service

- Status: Proposed, awaiting decision. Not authorized for implementation.
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
Nothing related to registration is implemented. Phase 2 adds only an
informational page and an inactive, clearly labelled entry point.
