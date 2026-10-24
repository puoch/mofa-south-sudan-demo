# Phase 3 report: diaspora registration prototype (synthetic data only)

Date: 24 October 2026. Phase 3 authorized by the project owner on
24 October 2026. Development stops here pending further authorization.

## Delivered

1. **Separate registration service** in `registration-service/` (Symfony 7.4
   LTS, PHP 8.3) with its own PostgreSQL database and role. Drupal holds no
   registration data and has no access to it (ADR 0010, 0013).
2. **Public form** with every field the specification confirms: full name,
   date of birth, nationality, email, phone, country and city of residence,
   passport number, issuing country and expiry. Passport details are
   optional (all three or none), and the form and the Drupal page both say
   that registration is optional, as the project owner asked. Server-rendered, no
   JavaScript, CSRF protection, a honeypot field and a rate limit of 10
   submissions per 15 minutes per address.
3. **Encrypted storage.** Passport details and date of birth are encrypted
   with libsodium before storage, in a separate table. A keyed hash finds
   possible duplicates, which are flagged for headquarters, not merged
   (ADR 0014).
4. **Reference numbers** such as `SSD-P4J6-NZMG-GDN1-9FZ7`: 75 random bits
   with a check character that catches any single mistyped character. Shown
   once on the confirmation page and never placed in a URL.
5. **Synthetic-only guards** (ADR 0015): passport numbers must look like
   `SYN123456`, emails must use test domains, a confirmation checkbox, a
   demonstration banner, database constraints, and a mode switch with no
   live setting.
6. **Append-only audit log** of submissions, enforced by a database trigger.
7. **Headquarters queue.** With no approved jurisdictions, every record is
   assigned to `hq_queue`.
8. **Drupal entry point.** The diaspora registration page links to the
   prototype only when `MOFA_REGISTRATION_PROTOTYPE_URL` is set; otherwise
   the button stays inactive, as in Phase 2.
9. **Draft privacy notice**, clearly marked as a placeholder.
10. **Commands:** `registry:seed-synthetic` and `registry:purge`.

## Tests

| Suite | Result |
| --- | --- |
| PHPUnit unit and functional tests (registration service) | 35 passed |
| Playwright: registration accessibility (axe, WCAG 2.2 AA), errors, confirmation, 320px reflow | 10 passed (desktop and mobile) |
| Full Playwright suite including the website | 132 passed, 32 skipped (desktop-only or mobile-only checks) |
| Drupal structure checks | 122 passed |
| gitleaks on the changes | No leaks |

The functional tests also prove that the database refuses unencrypted
passport numbers, non-synthetic rows and changes to the audit log.

Screenshots: `phase-3-screenshots/` in the project files.

## Not built (not authorized)

- Staff login, mission or headquarters access to records, exports or prints.
- Routing to missions, jurisdiction directory, row-level security.
- Corrections, retention tooling, confirmation emails.
- Any deployment, or any real personal or passport information.

## Open questions for the Ministry

See [approvals-and-blockers.md](approvals-and-blockers.md): eligibility and
minors, the country list, required fields, duplicate handling, key
management, privacy notice and hosting.
