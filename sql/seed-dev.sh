#!/usr/bin/env bash
#
# sql/seed-dev.sh — load the dev/demo catalog fixture into an existing database.
#
# setup-db.sh loads the schema + mtb_* masters only; its production contract
# deliberately excludes operational dtb_* data. This script adds the dev/demo
# operational data — products, categories, tags, customers, sample orders — so a
# freshly set-up database is immediately browsable: the storefront top page links
# to real product codes, and "click a product" resolves instead of 404ing.
#
# It (re)generates sql/seed/dev-fixture.sql from the Be Framework fake fixtures
# (be/var/fake/*.json) via build-dev-fixture.py, then loads it. Re-runnable: the
# fixture clears the product/customer/order tables it owns before re-INSERTing.
#
# Usage (same connection forms as setup-db.sh):
#   sql/seed-dev.sh DATABASE_URL
#   DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4' sql/seed-dev.sh
#
# Run it AFTER setup-db.sh (it needs the schema + masters already present).
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILDER="${SCRIPT_DIR}/seed/build-dev-fixture.py"
FIXTURE="${SCRIPT_DIR}/seed/dev-fixture.sql"

HOST=""
PORT="3306"
USER=""
PASS=""
DB=""

die() { echo "seed-dev: $*" >&2; exit 1; }

# Parse a DATABASE_URL of the form scheme://user:pass@host:port/dbname?query
# (same handling as setup-db.sh).
parse_url() {
    local url="$1"
    local rest="${url#*://}"
    rest="${rest%%\?*}"
    local userinfo="" hostpart="" dbpart=""
    if [[ "$rest" == *"@"* ]]; then
        userinfo="${rest%%@*}"
        local after="${rest#*@}"
        hostpart="${after%%/*}"
        dbpart="${after#*/}"
    else
        hostpart="${rest%%/*}"
        dbpart="${rest#*/}"
    fi
    USER="${userinfo%%:*}"
    if [[ "$userinfo" == *":"* ]]; then
        PASS="${userinfo#*:}"
    fi
    HOST="${hostpart%%:*}"
    if [[ "$hostpart" == *":"* ]]; then
        PORT="${hostpart#*:}"
    fi
    DB="$dbpart"
    USER="$(printf '%b' "${USER//%/\\x}")"
    PASS="$(printf '%b' "${PASS//%/\\x}")"
}

if [[ $# -ge 1 && "$1" != --* ]]; then
    parse_url "$1"
else
    [[ -n "${DATABASE_URL:-}" ]] || die "no DATABASE_URL given (pass it as an argument or set the env var)"
    parse_url "$DATABASE_URL"
fi

[[ -n "$HOST" ]] || die "host is required"
[[ -n "$USER" ]] || die "user is required"
[[ -n "$DB"   ]] || die "database name is required"
command -v python3 >/dev/null 2>&1 || die "python3 not found on PATH (needed to generate the fixture)"
command -v mysql   >/dev/null 2>&1 || die "mysql client not found on PATH"
[[ -r "$BUILDER" ]] || die "fixture generator missing/unreadable: $BUILDER"

mysql_run() {
    MYSQL_PWD="$PASS" mysql -h "$HOST" -P "$PORT" -u "$USER" "$@"
}

echo "seed-dev: target = ${USER}@${HOST}:${PORT}/${DB}"
echo "seed-dev: [1/2] generating fixture from be/var/fake/*.json ..."
python3 "$BUILDER"
[[ -r "$FIXTURE" ]] || die "fixture not generated: $FIXTURE"

echo "seed-dev: [2/2] loading dev-fixture.sql ..."
mysql_run --default-character-set=utf8mb4 "$DB" < "$FIXTURE"

products="$(mysql_run -N "$DB" -e 'SELECT COUNT(*) FROM dtb_product;' 2>/dev/null || echo '?')"
customers="$(mysql_run -N "$DB" -e 'SELECT COUNT(*) FROM dtb_customer;' 2>/dev/null || echo '?')"
echo "seed-dev: done. products=${products} customers=${customers}"
