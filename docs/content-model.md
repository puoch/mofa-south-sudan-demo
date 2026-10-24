# Content model

All content types use the Ministry editorial workflow (ADR 0005), keep
revisions and are translatable (ADR 0006).

## Editorial fields on every content type

| Field | Type | Notes |
| --- | --- | --- |
| Unverified placeholder content | Checkbox, on by default | Shows the placeholder notice while ticked. Untick only after verification and approval. |
| Sources | Links, unlimited | Official South Sudan government sources first, then authoritative international sources. Shown publicly. |
| Source and review notes | Long text | Internal only. Not displayed. |
| Last reviewed | Date | Shown publicly in "Sources and review". |
| Content owner | Text | Department or unit responsible. Shown publicly. |

## Content types

| Content type | Machine name | Purpose | Main fields |
| --- | --- | --- | --- |
| Basic page | `page` | Static pages and section landing pages | Summary, body, hero image |
| Country profile page | `national_profile` | About South Sudan gateway and its child pages | Summary, body, hero image, dated context notice and its date |
| Leadership profile | `leader_profile` | Ministry leadership | Position title (required), portrait, biography, display order |
| Diplomatic mission | `mission` | Embassies and other approved representations | Mission type, host country, city, address, telephone, email, website, head of mission, countries served, jurisdiction status (default: placeholder), image |
| News item | `news_item` | News, press releases, statements, announcements | Category (required), summary, body, image |
| Publication | `publication` | Official publications | Publication type, publication date, summary, document |
| Service information | `service` | Consular, immigration guidance, trade and investment, other | Service category, summary, body, service status (default: information only) |
| Archive record | `archive_record` | Historical documents not originating as news | Original date, collection, document, summary, body |

News, publications and other items move to the Archives section through the
**Archived** workflow state, so there is one archive mechanism for all types.
Archived items stay public; **Withdrawn** items are not.

## Listings (Phase 2)

| Path | Shows |
| --- | --- |
| `/news` | Current news items, newest first, with keyword search and a category filter |
| `/news/latest`, `/news/press-releases`, `/news/statements`, `/news/announcements` | Category pages (taxonomy term pages with these aliases), current items only |
| `/missions/directory` | Missions, A to Z, with a mission-type filter |
| `/about-mfaic/leadership` | Leadership profiles in display order |
| `/services/consular`, `/services/immigration`, `/services/trade-and-investment`, `/services/other` | Service category pages |
| `/archives/news` | Archived news, with search and category filter |
| `/archives/publications` | Current and archived publications, with search |
| `/archives/announcements` | Archive records (historical announcements) |
| `/home` (front page) | Hero (tagline from the site slogan), the three latest news items, service pathways, and the About South Sudan introduction |

Section and gateway pages list their child pages automatically from the main
menu, using each child page's summary as its description.

## Vocabularies

All are empty in Phase 1. Terms are added only from approved lists.

| Vocabulary | Used by |
| --- | --- |
| News category | News item |
| Service category | Service information |
| Publication type | Publication |
| Mission type | Diplomatic mission |
| Countries and territories | Mission host country and countries served. The choice of country list is itself pending approval. |

## Media

Core Image and Document media types (from Drupal core recipes). Image alt
text is required. Only authorized images may be uploaded.

## Roles

| Role | Can do |
| --- | --- |
| Content author | Create and edit own content and media; save drafts; submit for review |
| Content reviewer | Edit any content; return to draft; approve |
| Content publisher | Edit any content; publish, archive, restore to draft |
| Site manager | Manage menus, vocabularies and URL aliases; view reports |
| Administrator (Ministry ICT) | Everything. Hold this role only where strictly necessary. |

No editorial role can manage users, permissions, modules or site
configuration. Visitors cannot register accounts.

## Mission jurisdictions

The "Countries and territories served" field on a mission is display
information only and must match the Ministry-approved jurisdiction directory.
It is not used to route any records. The official directory and its owner
are pending decisions.
