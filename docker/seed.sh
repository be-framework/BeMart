#!/usr/bin/env bash
#
# docker/seed.sh — shared demo seeding used by the container entrypoint and by
# the periodic demo reset (docker/demo-reset.sh).
#
# Sourced, not executed: it defines configuration and three functions
# (mysql_db / wait_for_mysql / bemart_seed) and performs no work on its own.
#
# The seed mirrors sql/reset-dev.sh minus the Python fixture regeneration: it
# uses the committed sql/seed/dev-fixture.sql directly, so the image needs no
# Python.

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-eccubedb}"
URL="${DATABASE_URL:-mysql://root@db:3306/eccubedb?charset=utf8mb4}"

# Demo credentials. The defaults are local-development values committed to the
# repository; a publicly reachable deployment MUST pass its own via the
# environment so that no working credential is readable from the source.
MEMBER_PASSWORD="${BEMART_DEMO_MEMBER_PASSWORD:-local-dev-member-password}"
ADMIN_PASSWORD="${BEMART_DEMO_ADMIN_PASSWORD:-}"

mysql_db() { mysql --default-character-set=utf8mb4 -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$@"; }

bcrypt() { SEED_PASSWORD="$1" php -r "echo password_hash(getenv('SEED_PASSWORD'), PASSWORD_BCRYPT, ['cost' => 12]);"; }

# $1: log prefix
wait_for_mysql() {
    local prefix="$1" i
    echo "${prefix}: waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
    for i in $(seq 1 60); do
        if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --silent >/dev/null 2>&1; then
            return 0
        fi
        sleep 2
    done

    echo "${prefix}: ERROR — MySQL at ${DB_HOST}:${DB_PORT} unreachable after ~120s; aborting." >&2

    return 1
}

# Rebuild the demo database from scratch. setup-db.sh DROPs and re-CREATEs the
# target database, so this is also the recovery path for a half-finished seed.
#
# $1: log prefix
bemart_seed() {
    local prefix="$1" hash admin_hash

    echo "${prefix}: [1/5] schema + master data (setup-db.sh) ..."
    sql/setup-db.sh "$URL"
    echo "${prefix}: [2/5] dev fixture (demo member + orders) ..."
    mysql_db "$DB_NAME" < sql/seed/dev-fixture.sql
    echo "${prefix}: [3/5] IdeaStore themed catalog (収納/台所/家具… ; ~3000 products) ..."
    DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_NAME="$DB_NAME" \
        php sql/seed/load-idea-catalog.php
    echo "${prefix}: [4/5] enabling demo member login ..."
    hash="$(bcrypt "$MEMBER_PASSWORD")"
    mysql_db "$DB_NAME" \
        -e "UPDATE dtb_customer SET password='${hash}' WHERE email='login-test@example.com';"
    if [ -n "$ADMIN_PASSWORD" ]; then
        admin_hash="$(bcrypt "$ADMIN_PASSWORD")"
        mysql_db "$DB_NAME" \
            -e "UPDATE dtb_member SET password='${admin_hash}' WHERE login_id='test-admin';"
    fi
    echo "${prefix}: [5/5] writing seed-complete sentinel ..."
    mysql_db "$DB_NAME" -e "CREATE TABLE IF NOT EXISTS bemart_seed_complete (seeded_at datetime NOT NULL); TRUNCATE TABLE bemart_seed_complete; INSERT INTO bemart_seed_complete (seeded_at) VALUES (NOW());"
    echo "${prefix}: seed complete."
}
