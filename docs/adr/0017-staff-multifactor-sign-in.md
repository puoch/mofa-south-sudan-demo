# 0017. Staff sign-in with authenticator-app codes

- Status: Accepted for the Phase 4 prototype
- Date: 2026-10-24

## Decision
- Staff sign in with email, password and a 6-digit authenticator code
  (RFC 6238 TOTP), checked together. SMS codes are not supported.
- Every failure shows the same message, so the form does not reveal which
  part was wrong or whether an account exists or is deactivated.
- A code is accepted once per account (the last used time step is stored).
- Five failed attempts per account and address lock sign-in for 15 minutes.
- Sessions end after 15 minutes without activity. Cookies are `HttpOnly`,
  `SameSite=Strict` and `Secure` over HTTPS.
- Authenticator secrets are encrypted with the field key (ADR 0014).
- Accounts are created on the command line (`registry:staff:create`), which
  shows the password and secret once. Staff emails must be synthetic in the
  prototype. Accounts are deactivated, never deleted.

## Before any real use
- Decide whether the Ministry has a central sign-on to use instead.
- Add security keys (WebAuthn) for HQ roles, account recovery with a second
  officer's approval, and periodic access reviews.
