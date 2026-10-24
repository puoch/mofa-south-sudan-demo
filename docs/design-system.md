# Design system

The live reference is the component library at `/styleguide` (development
only). Source: `web/themes/custom/mofa_theme`.

## Direction
Modern, dignified and welcoming; suitable for an official diplomatic
institution. Neutral, highly readable base with a restrained dark-blue brand.

## Tokens (`css/tokens.css`)

| Token | Value | Use |
| --- | --- | --- |
| `--color-brand-navy` | `#14284b` | **Temporary.** Navigation, headings, buttons. Replace with the approved Ministry colour. |
| `--color-brand-navy-dark` | `#0c1a33` | **Temporary.** Footer, hover |
| `--color-text` | `#232d3b` | Body text (13.9:1 on white) |
| `--color-text-muted` | `#4b5666` | Secondary text (7.4:1 on white) |
| `--color-link` | `#1d4a8f` | Links (8.7:1 on white) |
| `--color-focus` | `#ffdd00` with `#0b0c0c` outer ring | Keyboard focus on any background |

National flag colours are not defined. No official HEX, RGB, CMYK or Pantone
values have been supplied, and the research report found none in official
sources. Do not sample them from images or third-party sites.

## Type
- Headings: Source Serif 4 (600)
- Body: IBM Plex Sans (400, 600, 700, italic)
- Arabic (later): IBM Plex Sans Arabic, loaded only for Arabic characters
- Fluid scale from `--font-size-100` to `--font-size-700`; line length capped at 68 characters.

## Components (`components/`)

| Component | Purpose |
| --- | --- |
| `demo-banner` | Site-wide "development demonstration, not official" notice |
| `site-identity` | Supplied coat of arms artwork beside the typographic MFA&IC wordmark |
| `placeholder-notice` | Full notice or compact badge for unverified content |
| `page-header` | Title with eyebrow, lead text and placeholder badge |
| `hero` | Opening band for home and gateway pages |
| `image-placeholder` | Stands in for authorized photos, maps and illustrations |
| `card` | Teaser for news, missions, services, publications |
| `link-tiles` | Pathways to child pages (About South Sudan gateway) |
| `quick-facts` | Country facts, each with its own source and approval status |
| `context-notice` | Dated, time-sensitive notice kept apart from evergreen text |
| `editorial-meta` | Sources, owner and last-reviewed date |
| `button` | Link button; renders as an inactive demonstration control without a URL |
| `alert` | Information, warning, success |

## Layouts
Home, gateway, listing and detail layouts are shown at
`/styleguide/layouts/{home,gateway,listing,detail}`. Phase 2 connects them to
Drupal content.

## Accessibility rules built in
- Skip link, landmarks, one `h1` per page, strict heading order
- Visible two-tone focus ring on every interactive element
- Minimum 44px targets for buttons, menu toggles and form fields
- Navigation uses the disclosure pattern and works without JavaScript
- Reduced-motion and forced-colours support
- Content reflows at 320px without horizontal scrolling
