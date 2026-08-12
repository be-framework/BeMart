#!/usr/bin/env bash
#
# docker/demo-reset.sh — restore the public demo to its seeded state.
#
# The demo is writable on purpose: checkout, registration, and the admin screens
# only mean something if a visitor can actually run them. The cost is residue —
# orders, members, uploaded files, edited masters. This script throws that away
# and rebuilds the database from the committed seed, so a visitor arriving at any
# time sees the same shop.
#
# Run it from inside the app container (it needs /app and the seed files):
#
#   docker compose exec -T app docker/demo-reset.sh
#
# Deployment note: the demo passwords come from BEMART_DEMO_MEMBER_PASSWORD /
# BEMART_DEMO_ADMIN_PASSWORD, so every reset re-applies the deployment's own
# credentials rather than the repository defaults.
#
# WARNING: destructive. setup-db.sh DROPs the target database.
set -euo pipefail
cd /app

# shellcheck source=docker/seed.sh
. docker/seed.sh

wait_for_mysql demo-reset
bemart_seed demo-reset

echo "demo-reset: products=$(mysql_db "$DB_NAME" -N -e 'SELECT COUNT(*) FROM dtb_product') customers=$(mysql_db "$DB_NAME" -N -e 'SELECT COUNT(*) FROM dtb_customer') orders=$(mysql_db "$DB_NAME" -N -e 'SELECT COUNT(*) FROM dtb_order')"
