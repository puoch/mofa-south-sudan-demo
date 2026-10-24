# 0014. Application-level encryption of passport details

- Status: Accepted for the Phase 3 prototype; key management must change before real use
- Date: 2026-10-24

## Decision
- Passport number, issuing country, expiry date, national ID number and
  date of birth are sealed
  with XChaCha20-Poly1305 (libsodium) before they reach the database. The
  column name is bound as associated data, so a value moved to another column
  fails to decrypt. Passport details are optional; when given, all three
  passport columns and the blind index must be present together (a database
  check enforces it). Ciphertexts carry a version prefix (`v1:`) for later key
  rotation, and a database check constraint rejects values without it.
- Duplicate passport and national ID numbers are found with a keyed BLAKE2b hash (a blind
  index) under a second, separate key. Duplicates are flagged for
  headquarters, never merged or rejected, until the Ministry decides how to
  handle them.
- In the prototype both keys come from environment variables
  (`REGISTRY_FIELD_KEY`, `REGISTRY_INDEX_KEY`) set in an uncommitted
  `.env.local`. Tests build fixed, obviously fake keys at runtime.

## Before any real use
- Move keys to a key management service with envelope encryption, access
  logging and rotation, owned by Ministry ICT.
- Decide whether passport numbers are masked on screen with a logged reveal.
- Review whether a keyed hash of a passport number is acceptable under the
  approved privacy notice.
