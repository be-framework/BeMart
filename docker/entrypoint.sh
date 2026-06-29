#!/usr/bin/env bash
#
# BeMart container entrypoint: wait for MySQL, seed once (idempotent), then serve.
#
# The seed mirrors sql/reset-dev.sh minus the Python fixture regeneration: it
# uses the committed sql/seed/dev-fixture.sql directly, so the image needs no
# Python. Seeding is gated on dtb_product being empty, so the named DB volume
# persists data across restarts and only the first boot pays the seed cost.
set -euo pipefail
cd /app

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-eccubedb}"
URL="${DATABASE_URL:-mysql://root@db:3306/eccubedb?charset=utf8mb4}"

echo "entrypoint: waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
for _ in $(seq 1 60); do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --silent >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

seeded="$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -N \
    -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.dtb_product" 2>/dev/null || echo 0)"

if [ "${seeded:-0}" -gt 0 ]; then
    echo "entrypoint: database already seeded (products=${seeded}); skipping seed."
else
    echo "entrypoint: [1/4] schema + master data (setup-db.sh) ..."
    sql/setup-db.sh "$URL"
    echo "entrypoint: [2/4] dev fixture (demo member + orders) ..."
    mysql --default-character-set=utf8mb4 -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" \
        < sql/seed/dev-fixture.sql
    echo "entrypoint: [3/4] IdeaStore themed catalog (収納/台所/家具… ; ~3000 products) ..."
    DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_NAME="$DB_NAME" \
        php sql/seed/load-idea-catalog.php
    echo "entrypoint: [4/4] enabling demo member login ..."
    HASH="$(php -r "echo password_hash('local-dev-member-password', PASSWORD_BCRYPT, ['cost' => 12]);")"
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" \
        -e "UPDATE dtb_customer SET password='${HASH}' WHERE email='login-test@example.com';"
    echo "entrypoint: seed complete."
fi

echo "entrypoint: serving BeMart on http://0.0.0.0:8080 (context=${APP_CONTEXT:-html-eccube-sql-hal-app})"
exec php -d memory_limit=512M -S 0.0.0.0:8080 -t public public/page.php
