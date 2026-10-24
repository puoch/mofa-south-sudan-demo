# Editor guide (development demonstration)

This guide is for Ministry staff who will add and approve content. It
describes the demonstration site; steps may change before launch.

## Signing in
Go to `/user/login`. Accounts are created by an administrator; visitors
cannot register. (Two-factor sign-in will be required before any shared
staging or live site.)

## Roles

| Role | What you can do |
| --- | --- |
| Content author | Create content and media, save drafts, submit for review |
| Content reviewer | Edit any content, approve it, or return it to draft with comments |
| Content publisher | Publish approved content, archive it, or withdraw it |
| Site manager | Manage menus, categories and URL aliases |

## The approval workflow

1. **Draft**: the author writes the item and saves it.
2. **In review**: the author chooses "Submit for review".
3. **Approved for publication**: a reviewer checks facts, sources and
   wording, then chooses "Approve" (or "Return to draft").
4. **Published**: a publisher chooses "Publish". Only approved items can be
   published.
5. **Archived**: older items stay public in the Archives section.
6. **Withdrawn**: takes an item off the public site.

Editing a published item creates a new draft; the published version stays
live until the new draft is approved and published. Every save keeps a
revision, so earlier versions can be compared and restored.

## Writing an item

1. Go to **Content > Add content** and choose the type (see the table in
   `docs/content-model.md`).
2. Fill in the title, summary (one or two sentences) and body.
3. Fill in **Sources**: official South Sudan government sources first, then
   authoritative international sources.
4. Fill in **Content owner** and **Last reviewed**.
5. Use **Source and review notes** for anything reviewers need to know; it is
   never shown publicly.
6. Set the URL alias (for example `/news/2026-10-ministry-statement`).
7. Leave **Unverified placeholder content** ticked until the item has been
   verified and approved. Untick it only as part of approval.

## Rules that always apply
- Do not publish facts that are still pending approval: population figures,
  maps, administrative boundaries, mission jurisdictions, officeholders,
  political transition details. See `docs/approvals-and-blockers.md`.
- Put time-sensitive information in the **Dated context notice** on Country
  profile pages, with its date, not in the main text.
- Use only authorized images, and write alternative text that describes what
  the image shows.
- Services are "Information only" unless the Ministry approves an online
  service.
- Never enter real personal information on the demonstration site.

## Menus and section pages
Section pages list their child pages automatically from the main menu. To
add a child page, create the page, then add it under its section in
**Structure > Menus > Main navigation**. (During development the menus are
rebuilt from `navigation.yml`; ask the technical team before changing them.)
