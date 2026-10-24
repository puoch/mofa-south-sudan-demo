# 0006. English at launch, structured for Arabic later

- Status: Accepted
- Date: 2026-10-24

## Decision
Language and Content Translation are enabled. English is the only language.
Every content type, the image and document media types and all vocabularies
are marked translatable, so adding Arabic later is a configuration task.

The theme is prepared for right-to-left: `html` carries Drupal's `lang` and
`dir`, all CSS uses logical properties, directional icons flip under
`[dir="rtl"]`, and IBM Plex Sans Arabic is declared for Arabic characters.

## Not decided
- The Arabic rollout date and whether it means Modern Standard Arabic or
  another variety (the research report warns not to treat "Arabic" and
  "Juba Arabic" as interchangeable).
- Whether other national languages are added.
- Interface translation (the Locale module) is not enabled because it fetches
  translations from drupal.org; enable it in the Arabic phase.
