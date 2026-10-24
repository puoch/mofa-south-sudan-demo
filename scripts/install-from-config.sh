#!/usr/bin/env bash
# Install a fresh development site from the exported configuration and build
# the navigation. Destroys the current site's database content.
set -euo pipefail
cd "$(dirname "$0")/.."

DRUSH="vendor/bin/drush"
ADMIN_PASS="${ADMIN_PASS:-$(php -r 'echo bin2hex(random_bytes(8));')}"

$DRUSH site:install --existing-config --account-name=admin --account-pass="$ADMIN_PASS" -y
$DRUSH mofa:navigation
$DRUSH cache:rebuild
$DRUSH php:script tests/drush/structure-check.php

echo
echo "Development site installed. Admin user: admin / $ADMIN_PASS (local only)."
