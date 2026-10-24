# Static demonstration copy

`scripts/build-static.sh` makes a static, read-only copy of the website in
`build/static/`, with `index.html` as the home page. The
`Static demo site (GitHub Pages)` workflow builds it from a clean install on
every push to `main` and publishes it with GitHub Pages.

## What it contains

- Every public page with its placeholder content, the demonstration banner
  and the `noindex` instruction. The build fails if a page lacks either.
- No forms: search and filter forms are replaced with a note, so the copy
  accepts no input. The registration button stays inactive.
- No sign-in pages, component library, registration prototype or staff
  console, which all need a server.
- `robots.txt` disallowing all crawlers.

## Visibility

A GitHub Pages site is public to anyone with its address. The project owner
approved publishing it for assessment on 24 October 2026. It is not an
official site and must not be presented as one.

## Turning it on

In the repository's Settings > Pages, set Source to "GitHub Actions". The
next push to `main` (or a manual run of the workflow) publishes the site.

## Building locally

```bash
./scripts/install-from-config.sh
(cd web && php -S 127.0.0.1:8080 .ht.router.php &)
./scripts/build-static.sh
cd build/static && python3 -m http.server 8090
```
