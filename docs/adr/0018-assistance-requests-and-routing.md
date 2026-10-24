# 0018. Diaspora help and enquiry requests, and how they are routed

- Status: Proposed (prototype only)
- Date: 2026-10-24

## Context
The project owner asked for a form where a member of the diaspora in
distress, or with an enquiry, gives their contact details, describes the
situation, and says where they are, with the request going to headquarters
and to the nearest mission. The Ministry has not approved a jurisdiction
directory, an emergency response process, or any email or SMS provider, and
the standing rules forbid real data, invented missions and real sends.

## Decision
1. The form lives in the registration service (`/assistance`), not in
   Drupal, and follows every guard in ADR 0015: synthetic-only email
   domains, a "made up" confirmation, the demonstration banner, no caching,
   `noindex`, and a database that refuses non-synthetic rows.
2. The page says plainly that it is not an emergency service and that anyone
   in immediate danger should contact local emergency services. The person
   must tick a box confirming they understand this.
3. Location is typed in (country, city, optional description). The browser
   is not asked for the device's position, and `Permissions-Policy` still
   blocks geolocation.
4. The description and location details are free text, so they are
   encrypted with the same field cipher as passport details.
5. Every request goes to headquarters; a database check enforces it. A
   request also goes to the mission that serves the country the person is
   in, chosen by the same `MissionRouter` and jurisdiction rules that route
   registrations (ADR 0016). When no single approved mission serves the
   country, the request stays with headquarters. In the prototype the
   missions and rules are the synthetic ones from `registry:seed-missions`;
   none of them is a real mission or jurisdiction.
6. "Nearest mission" means the mission whose jurisdiction covers the
   country, not physical distance. Distance would need mission coordinates
   and the person's position, neither of which is approved.
7. Nothing is sent. The routing is written to the append-only audit log and
   the application log (reference number only) and shown on the
   confirmation page.

## Consequences
- Real mission routing follows automatically once the approved jurisdiction
  directory is loaded as rules. Real notifications need an approved provider and a
  separate reviewed change.
- Staff cannot see requests yet: the staff console lists registrations
  only. A real service also needs response targets and out-of-hours cover
  before it can open.
