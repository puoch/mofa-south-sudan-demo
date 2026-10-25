"""Turns one page of the static demo copy into a single, self-contained HTML
file that opens on its own: stylesheets, scripts, fonts and images are
inlined, and links to other pages point at the published demo.

Usage: python3 scripts/make-single-page.py build/static index.html OUT.html \
         https://puoch.github.io/mofa-south-sudan-demo/
"""
import base64
import html
import mimetypes
import pathlib
import re
import sys
from urllib.parse import urljoin

root = pathlib.Path(sys.argv[1])
page = root / sys.argv[2]
out = pathlib.Path(sys.argv[3])
live = sys.argv[4].rstrip('/') + '/'

mimetypes.add_type('font/woff2', '.woff2')


def local(ref: str, base: pathlib.Path) -> pathlib.Path:
    return (base.parent / html.unescape(ref).split('#')[0]).resolve()


def data_uri(path: pathlib.Path) -> str:
    kind = mimetypes.guess_type(path.name)[0] or 'application/octet-stream'
    return f'data:{kind};base64,' + base64.b64encode(path.read_bytes()).decode()


def inline_css(css_path: pathlib.Path) -> str:
    css = css_path.read_text(encoding='utf-8')

    def repl(m):
        ref = m.group(2)
        if ref.startswith(('data:', 'http:', 'https:', '#')):
            return m.group(0)
        target = local(ref.split('?')[0], css_path)
        return f'url("{data_uri(target)}")' if target.exists() else m.group(0)

    return re.sub(r'url\((["\']?)([^)"\']+)\1\)', repl, css)


doc = page.read_text(encoding='utf-8')

doc = re.sub(
    r'<link[^>]*rel="stylesheet"[^>]*href="([^"]+)"[^>]*/?>',
    lambda m: '<style>\n' + inline_css(local(m.group(1), page)) + '\n</style>',
    doc,
)
doc = re.sub(
    r'<script([^>]*)\ssrc="([^"]+)"([^>]*)></script>',
    lambda m: '<script' + m.group(1) + m.group(3) + '>\n'
    + local(m.group(2), page).read_text(encoding='utf-8').replace('</script', '<\\/script') + '\n</script>',
    doc,
)
doc = re.sub(r'<img([^>]*)\ssrc="([^"]+)"', lambda m: f'<img{m.group(1)} src="{data_uri(local(m.group(2), page))}"', doc)
# The 1x image is now inline; drop srcset rather than inline every size.
doc = re.sub(r'\ssrcset="[^"]*"', '', doc)
doc = re.sub(r'<link[^>]*rel="(icon|shortcut icon)"[^>]*>', '', doc)


def link(m):
    ref = m.group(1)
    if ref.startswith(('http:', 'https:', 'mailto:', 'tel:', '#', 'data:')):
        return m.group(0)
    return f'href="{urljoin(urljoin(live, sys.argv[2]), html.unescape(ref))}"'


doc = re.sub(r'href="([^"]*)"', link, doc)

out.write_text(doc, encoding='utf-8')
print(f'{out} written ({out.stat().st_size // 1024} KB)')
