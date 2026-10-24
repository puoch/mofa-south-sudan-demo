# 0004. Custom theme with design tokens and single-directory components

- Status: Accepted
- Date: 2026-10-24

## Decision
`mofa_theme` has no base theme. Colours, type, spacing and shape are CSS
custom properties in `css/tokens.css`. Reusable parts are Drupal
single-directory components (SDC) in `components/`, each with a schema, a
Twig template and its own CSS. Layout primitives (`l-container`, `l-grid`,
`l-with-sidebar`, `l-section`) compose pages.

## Branding constraints
- `--color-brand-navy` is a **temporary** token following the agreed dark-blue
  direction. It is not an official colour and must be replaced when the
  Ministry supplies an approved specification.
- No national flag colours are defined: no official digital values have been
  found or supplied (the research report confirms this).
- The coat of arms uses artwork supplied by the project owner on 24 October
  2026 (a 512px PNG of the coat of arms in its circular seal). It is used
  unaltered, only resized. A vector master from an authorized source should
  replace it; the artwork must never be redrawn.
- The blue in the seal artwork is not used as a design token: the official
  dark-blue specification is still pending.
- The MFA&IC wordmark is set in type until an approved wordmark exists.

## Consequences
- Rebranding is a token change, not a template change.
- All layout CSS uses logical properties so right-to-left works later.
