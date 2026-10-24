# 0016. Staff access, routing and row-level security

- Status: Accepted for the Phase 4 prototype
- Date: 2026-10-24

## Decision
- **Roles.** Each staff account has one role: mission staff (bound to one
  mission), HQ assignment officer, HQ oversight officer or account
  administrator. Permissions follow the role matrix in the architecture
  review:

  | Action | Mission staff | HQ assignment | HQ oversight | Account admin |
  | --- | --- | --- | --- | --- |
  | View records | Own mission | All | All | None |
  | Reveal passport and national ID numbers | Logged | No | Logged | No |
  | Reassign a record | No | Yes, with a reason | No | No |
  | Read the audit log | No | No | Yes | No |
  | Activate or deactivate accounts | No | No | No | Yes |

- **Routing.** A record goes to the single approved resident mission for
  the country of residence, else the single approved non-resident mission,
  else the headquarters queue. Draft missions and unapproved rules never
  receive records. Overlaps go to the queue.
- **Row-level security.** PostgreSQL policies on `registration`,
  `registration_sensitive` and `assignment_history` read `app.scope` and
  `app.mission`, which the console sets for every request. Mission scope sees
  only its mission's rows and cannot move rows in or out; the accounts scope
  sees none. `FORCE ROW LEVEL SECURITY` applies the policies to the table
  owner the app connects as.
- **Logging.** Sign-ins (successful and failed), record views, reveals,
  reassignments and account changes go to the append-only audit log.
  Reassignments also go to an assignment history that cannot be edited.
- **Not built.** Exports, printing grants, corrections and retention tools:
  their rules are pending. Printing a record shows only a notice.

## Limits of the prototype
- When `app.scope` is unset (the public form, command-line tools), policies
  allow everything. A production design should use separate database roles
  per scope (public insert-only, mission, HQ, maintenance) so that a missing
  setting fails closed.
- The watermark names the viewer, but nothing can stop screenshots. Policy
  and training must say that viewing is controlled and logged, not that
  copying is impossible.
