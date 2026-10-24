# 0009. Navigation kept in code

- Status: Accepted
- Date: 2026-10-24

## Decision
The proposed information architecture lives in
`web/modules/custom/mofa_core/navigation.yml`. `drush mofa:navigation`
creates or updates the main and footer menus from it and is safe to run
repeatedly.

## Consequences
- The IA is reviewable in pull requests and reproducible on every
  environment.
- Site managers can still reorder or disable links in the UI, but the next
  run of the command restores the file's order. Once the Ministry approves
  the IA, menus can be handed over to editors and the command retired.
- Every menu path resolves to a placeholder page, listing or category page
  (Phase 2); a test fails if any menu link returns an error.
