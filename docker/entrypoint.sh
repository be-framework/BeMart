#!/usr/bin/env bash
#
# BeMart container entrypoint: wait for MySQL, seed once (idempotent), then serve.
#
# Idempotency is gated on a success sentinel table (bemart_seed_complete) written
# only AFTER the full seed finishes. A run that fails partway leaves no sentinel,
# so the next boot re-seeds from scratch (setup-db.sh drops the database first,
# clearing any partial state) instead of skipping forever on a stray dtb_product
# row left by a half-completed seed.
#
# The seed itself lives in docker/seed.sh, shared with docker/demo-reset.sh.
set -euo pipefail
cd /app

# shellcheck source=docker/seed.sh
. docker/seed.sh

wait_for_mysql entrypoint

seeded="$(mysql_db -N -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.bemart_seed_complete" 2>/dev/null || echo 0)"

if [ "${seeded:-0}" -gt 0 ]; then
    echo "entrypoint: database already seeded; skipping seed."
else
    bemart_seed entrypoint
fi

echo "entrypoint: serving BeMart on http://0.0.0.0:8080 (context=${APP_CONTEXT:-html-eccube-sql-hal-app})"
exec php -d memory_limit=512M -S 0.0.0.0:8080 -t public public/page.php
