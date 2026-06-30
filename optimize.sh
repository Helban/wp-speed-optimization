#!/usr/bin/env bash
# Apply the in-place optimization and switch the page to its "after" state.
# Run after setup.sh has built the "before" site.
set -euo pipefail
cd "$(dirname "$0")"

wp() { docker compose run --rm wpcli wp "$@"; }

wp eval-file /provisioning/import_after_images.php
wp plugin deactivate happy-elementor-addons ml-slider font-awesome
wp --exec="define('SPEED_VARIANT','after');" eval-file /provisioning/build_home.php

echo "Optimized 'after' state ready. mu-plugins/speed-optimization.php is now active."
