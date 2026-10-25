# Single-file demo page

`index.html` is the home page of the static demonstration copy as one
self-contained file: styles, fonts, script and the coat of arms are inlined,
so it opens directly in a browser with no other files and makes no network
requests. Links to other pages go to the published demo at
https://puoch.github.io/mofa-south-sudan-demo/.

It is placeholder content only, with the demonstration banner, and is not an
official Ministry page.

Regenerate it after `scripts/build-static.sh`:

```bash
python3 -I scripts/make-single-page.py build/static index.html demo/index.html https://puoch.github.io/mofa-south-sudan-demo/
```
