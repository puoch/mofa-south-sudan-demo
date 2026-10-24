# 0011. Placeholder content kept in code

- Status: Accepted (Phase 2)
- Date: 2026-10-24

## Decision
The Phase 2 placeholder pages, synthetic news, missions, leadership profiles,
services, publications, archive records and the category terms live in
`web/modules/custom/mofa_core/placeholder_content.yml`.
`drush mofa:placeholder-content` creates or updates them, matched by path
alias. Every item has "Unverified placeholder content" ticked.

## Rules for the file
- No real officeholders, missions, jurisdictions, contact details,
  statistics, maps or policies.
- Body text says what the page will contain, not what the facts are.
- Internal notes record pending decisions (shown to editors only).

## Consequences
- Every environment shows the same demonstration content.
- Running the command overwrites edits to these items. Stop using it once
  the Ministry starts editing real content, then delete placeholder items
  as approved content replaces them.
