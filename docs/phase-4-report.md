# Phase 4 report: staff and mission access (synthetic data only)

Date: 24 October 2026. Phase 4 authorized by the project owner on
24 October 2026. Development stops here pending further authorization.

## Delivered

1. **Missions and routing** (ADR 0016). Synthetic missions ("Demonstration
   Mission A") and routing rules. New records go to the one approved mission
   that serves the country of residence, or to the headquarters queue.
2. **Staff console** at `/staff` in the registration prototype, with four
   roles: mission staff, HQ assignment officer, HQ oversight officer and
   account administrator, following the architecture review's role matrix.
3. **Multifactor sign-in** (ADR 0017): password plus authenticator-app code,
   one generic error message, no code reuse, login throttling and a
   15-minute idle timeout.
4. **Row-level security in PostgreSQL**: mission staff see only their own
   mission's records even if application code forgets a filter. Account
   administrators see no records at all.
5. **Records.** List and detail views. Passport and national ID numbers are
   masked; revealing them is a logged action for the roles allowed to do it.
   Each view carries a watermark naming the viewer, and printing shows only
   a notice.
6. **Reassignment** by HQ assignment officers to an approved mission or the
   queue, with a required reason, recorded in an assignment history that
   cannot be edited.
7. **Audit log** for oversight officers: sign-ins, views, reveals,
   reassignments and account changes.
8. **Account administration**: activate and deactivate accounts in the
   console; create them with `registry:staff:create`.
9. **Demo data**: `registry:seed-missions` and `registry:seed-staff`, which
   takes its password and authenticator secret from environment variables.

## Tests

| Suite | Result |
| --- | --- |
| PHPUnit (registration service), including routing, row-level security, masking, reveal logging, roles, code reuse | 52 passed |
| Playwright: staff console sign-in, roles, masking and accessibility (axe, WCAG 2.2 AA), 320px reflow | 12 passed (desktop and mobile) |
| Full Playwright suite | See the PR checks |

## Not built (not authorized or pending Ministry rules)

- Exports, print grants, corrections, retention and deletion tooling.
- Real missions, real jurisdictions, real staff or real records.
- Central sign-on, security keys, deployment of any kind.
