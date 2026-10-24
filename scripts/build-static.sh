#!/usr/bin/env bash
# Builds a static, read-only copy of the demonstration website in
# build/static/ (index.html and one folder per page), for review or for
# GitHub Pages. Run it against a site installed from config with placeholder
# content, for example after scripts/install-from-config.sh.
#
# The copy contains placeholder content only. Forms are removed, because a
# static copy cannot and must not collect anything. The registration
# prototype and staff console need a server and are not included.
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
OUT="${OUT:-build/static}"

rm -rf "$OUT"
mkdir -p "$OUT"

# wget exits 8 when some links are missing (for example excluded pages);
# that is expected here.
wget --mirror --page-requisites --convert-links --adjust-extension \
  --no-host-directories --restrict-file-names=windows --no-verbose \
  -e robots=off \
  --reject-regex '/(user|styleguide|node|admin|search|filter|contextual|batch|rss\.xml)(/|$|\?)|\?(keys|category|type|page)=' \
  --directory-prefix="$OUT" \
  "$BASE_URL/" || [ $? -eq 8 ]

python3 "$(dirname "$0")/static-postprocess.py" "$OUT" "$BASE_URL"
echo "Static site written to $OUT"
