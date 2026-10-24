# 0015. Guards that keep the prototype synthetic

- Status: Accepted
- Date: 2026-10-24

## Context
The authorization forbids collecting real diaspora or passport information,
and the development environment is not approved for sensitive data. Labels
alone do not stop someone typing real details.

## Decision
The prototype refuses real-looking data at several layers:
1. Passport numbers must match `SYN` followed by six digits, checked by the
   form and again in the service that stores records.
2. Email addresses must use domains reserved for testing (example.com,
   example.org, example.net, `.example`, `.test`).
3. A required checkbox confirms every detail is made up.
4. Every page shows a demonstration banner, is marked `noindex` and is never
   cached.
5. The database rejects any row whose `data_class` is not `synthetic`, and
   any sensitive column that is not encrypted.
6. Every page returns 503 unless `REGISTRY_MODE=synthetic`. There is no live
   mode in the code.

The audit log is append-only: a database trigger rejects UPDATE, DELETE and
TRUNCATE on `audit_event`. Audit entries hold the reference number, never a
name or passport detail.

## Consequences
Moving to real data needs deliberate code changes (new validation rules, a
new mode, a migration that relaxes the check constraint), each of which will
show up in review. That is intended.
