#!/usr/bin/env bash
#
# BeMart container entrypoint: wait for MySQL, seed once (idempotent), then serve.
#
# The seed mirrors sql/reset-dev.sh minus the Python fixture regeneration: it
# uses the committed sql/seed/dev-fixture.sql directly, so the image needs no
# Python.
#
# Idempotency is gated on a success sentinel table (bemart_seed_complete) written
# only AFTER the full seed finishes. A run that fails partway leaves no sentinel,
# so the next boot re-seeds from scratch (setup-db.sh drops the database first,
# clearing any partial state) instead of skipping forever on a stray dtb_product
# row left by a half-completed seed.
set -euo pipefail
cd /app

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-eccubedb}"
URL="${DATABASE_URL:-mysql://root@db:3306/eccubedb?charset=utf8mb4}"

mysql_db() { mysql --default-character-set=utf8mb4 -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$@"; }

echo "entrypoint: waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
ready=0
for _ in $(seq 1 60); do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --silent >/dev/null 2>&1; then
        ready=1
        break
    fi
    sleep 2
done
if [ "$ready" -ne 1 ]; then
    echo "entrypoint: ERROR — MySQL at ${DB_HOST}:${DB_PORT} unreachable after ~120s; aborting." >&2
    exit 1
fi

seeded="$(mysql_db -N -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.bemart_seed_complete" 2>/dev/null || echo 0)"

if [ "${seeded:-0}" -gt 0 ]; then
    echo "entrypoint: database already seeded; skipping seed."
else
    echo "entrypoint: [1/5] schema + master data (setup-db.sh) ..."
    sql/setup-db.sh "$URL"
    echo "entrypoint: [2/5] dev fixture (demo member + orders) ..."
    mysql_db "$DB_NAME" < sql/seed/dev-fixture.sql
    echo "entrypoint: [3/5] IdeaStore themed catalog (収納/台所/家具… ; ~3000 products) ..."
    DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_NAME="$DB_NAME" \
        php sql/seed/load-idea-catalog.php
    echo "entrypoint: [4/5] enabling demo member login ..."
    HASH="$(php -r "echo password_hash('login-test-password-2026', PASSWORD_BCRYPT, ['cost' => 12]);")"
    mysql_db "$DB_NAME" \
        -e "UPDATE dtb_customer SET password='${HASH}' WHERE email='login-test@example.com';"
    echo "entrypoint: [5/5] writing seed-complete sentinel ..."
    mysql_db "$DB_NAME" -e "CREATE TABLE IF NOT EXISTS bemart_seed_complete (seeded_at datetime NOT NULL); INSERT INTO bemart_seed_complete (seeded_at) VALUES (NOW());"
    echo "entrypoint: seed complete."
fi

echo "entrypoint: serving BeMart on http://0.0.0.0:8080 (context=${APP_CONTEXT:-html-eccube-sql-hal-app})"
exec php -d memory_limit=512M -S 0.0.0.0:8080 -t public public/page.php
