#!/usr/bin/env bash
# Build the "before" state: a typical bloated Elementor + OceanWP site, served
# at http://localhost:8080. Run optimize.sh afterwards to produce the "after".
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Created .env from .env.example (local-only credentials)."
fi
set -a; . ./.env; set +a

docker compose up -d

wp() { docker compose run --rm wpcli wp "$@"; }

wp core install --url="$WP_SITE_URL" --title="$WP_SITE_TITLE" \
  --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email="$WP_ADMIN_EMAIL" --skip-email

wp theme install oceanwp --activate
wp plugin install elementor happy-elementor-addons contact-form-7 ml-slider font-awesome add-to-any --activate

python3 -m venv .venv
.venv/bin/pip install -q pillow httpx
.venv/bin/python scripts/make_before_images.py
.venv/bin/python scripts/make_after_images.py
.venv/bin/python scripts/make_rebuild_images.py

wp eval-file /provisioning/import_before_images.php
wp eval-file /provisioning/build_home.php

echo "Before state ready at $WP_SITE_URL"
