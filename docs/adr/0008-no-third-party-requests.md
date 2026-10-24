# 0008. No third-party requests from public pages

- Status: Accepted
- Date: 2026-10-24

## Decision
Fonts are self-hosted (SIL Open Font License). There is no analytics, no
external CDN, no embedded maps or video. A test fails if a page requests any
host other than the site itself.

## Consequences
Any future analytics, CAPTCHA or embedded service needs a privacy review and
approval by the governance group.
