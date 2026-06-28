#!/usr/bin/env bash
#
# reset-dev.sh — restore the dev/SQL database to a known, deterministic state.
#
# Run this BEFORE any browser/visual verification run. Mutating operations
# (checkout creates orders, CSV upload accumulates rows, …) leave residue, so
# without a reset the Nth run sees different data. This rebuilds the database
# from the EC-CUBE schema + master seed + the fake-derived dev fixture, so every
# run starts from the same point.
#
#   sql/reset-dev.sh                      # default: eccubedb on 127.0.0.1:3306 root
#
set -euo pipefail
cd "$(dirname "$0")/.."

HOST="${DB_HOST:-127.0.0.1}"
PORT="${DB_PORT:-3306}"
USER="${DB_USER:-root}"
DB="${DB_NAME:-eccubedb}"
URL="mysql://${USER}@${HOST}:${PORT}/${DB}?charset=utf8mb4"
MYSQL=(mysql --default-character-set=utf8mb4 -h "$HOST" -P "$PORT" -u "$USER" "$DB")

echo "reset-dev: [1/4] regenerating fixture from be/var/fake/*.json ..."
python3 sql/seed/build-dev-fixture.py

echo "reset-dev: [2/4] rebuilding schema + master + test-admin (setup-db.sh) ..."
sql/setup-db.sh "$URL" >/dev/null

echo "reset-dev: [3/4] loading the dev fixture (utf8mb4) ..."
"${MYSQL[@]}" < sql/seed/dev-fixture.sql

echo "reset-dev: [3b/4] loading IdeaStore themed catalog (収納/台所/家具… ; skipped if absent) ..."
DB_HOST="$HOST" DB_PORT="$PORT" DB_USER="$USER" DB_NAME="$DB" php sql/seed/load-idea-catalog.php

echo "reset-dev: [4/4] making the PoC test customer loginnable ..."
HASH="$(php -r "echo password_hash('local-dev-member-password', PASSWORD_BCRYPT, ['cost' => 12]);")"
"${MYSQL[@]}" -e "UPDATE dtb_customer SET password='${HASH}' WHERE email='login-test@example.com';"

echo "reset-dev: done. products=$("${MYSQL[@]}" -N -e 'SELECT COUNT(*) FROM dtb_product') customers=$("${MYSQL[@]}" -N -e 'SELECT COUNT(*) FROM dtb_customer') orders=$("${MYSQL[@]}" -N -e 'SELECT COUNT(*) FROM dtb_order')"
echo "reset-dev: admin=test-admin/local-dev-admin-password (2FA dev=123456)  member=login-test@example.com/local-dev-member-password"
