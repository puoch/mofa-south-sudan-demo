"""Post-processes the static website copy made by build-static.sh.

- Removes every <form>: a static copy must not appear to accept input.
  Listing pages get a short note instead of their search and filter form.
- Turns links that still point at the source server (pages left out of the
  copy, such as sign-in) into plain text.
- Adds robots.txt and .nojekyll, and checks that every page still carries
  the demonstration banner and the noindex instruction.
"""
import pathlib
import re
import sys

out = pathlib.Path(sys.argv[1])
base = sys.argv[2].rstrip('/')

FORM = re.compile(r'<form\b.*?</form>', re.S | re.I)
NOTE = ('<p class="static-note"><em>Search and filters are not available in this '
        'static copy of the demonstration site.</em></p>')
SOURCE_LINK = re.compile(r'<a\b[^>]*href="' + re.escape(base) + r'[^"]*"[^>]*>(.*?)</a>', re.S | re.I)

pages = list(out.rglob('*.html'))
missing = []
for page in pages:
    html = page.read_text(encoding='utf-8')
    html = FORM.sub(NOTE, html)
    html = SOURCE_LINK.sub(r'<span>\1</span>', html)
    if base in html:
        html = html.replace(base, '')
    if 'name="robots" content="noindex' not in html or 'demo-banner' not in html:
        missing.append(str(page.relative_to(out)))
    page.write_text(html, encoding='utf-8')

(out / 'robots.txt').write_text('User-agent: *\nDisallow: /\n', encoding='utf-8')
(out / '.nojekyll').write_text('', encoding='utf-8')

if missing:
    sys.exit('Pages without the demonstration banner or noindex: ' + ', '.join(missing))
print(f'{len(pages)} pages processed')
