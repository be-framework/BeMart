#!/usr/bin/env bash
#
# sql/setup-db.sh — reproducible BeMart production database bring-up.
#
# Creates the target database, loads the BeMart schema, applies BeMart
# schema migrations, then loads the mtb_* master/reference seed and the
# dtb_* system master rows. The result is a database with the full schema and
# all canonical reference data — ready for dtb_* operational data, which is
# migrated separately and is OUT OF SCOPE for this script.
#
# Usage:
#   sql/setup-db.sh DATABASE_URL
#   sql/setup-db.sh --url 'mysql://user:pass@host:port/dbname?...'
#   sql/setup-db.sh --host 127.0.0.1 --port 3306 --user root \
#                   --pass '' --db eccubedb
#
# DATABASE_URL form (Symfony/Doctrine style, query string optional):
#   mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4
#
# Environment:
#   If no arguments are given, DATABASE_URL is read from the environment.
#
# Behavior notes:
#   - The script is idempotent: the target database is DROPped and re-CREATEd
#     (DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin) on every run. This
#     is required because the schema uses bare `CREATE TABLE` (not
#     `CREATE TABLE IF NOT EXISTS`), so a fresh database is the only way to
#     re-load it cleanly.
#     WARNING: any existing data in the target database is destroyed. Pass a
#     scratch/fresh database name; never point this at a populated prod DB.
#   - The schema (sql/schema/bemart-schema.sql) carries cross-table FOREIGN
#     KEY constraints. We wrap the schema load with FK checks disabled to
#     allow any table ordering — the same workaround the bemart-sql test
#     bootstrap (be/tests/Sql/bootstrap.php) uses.
#   - The seed (sql/seed/mtb-master.sql) additionally TRUNCATEs then
#     re-INSERTs each mtb_* table, so it is self-idempotent if applied alone.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="${SCRIPT_DIR}/schema/bemart-schema.sql"
MIGRATIONS_DIR="${SCRIPT_DIR}/migrations"
SEED_FILE="${SCRIPT_DIR}/seed/mtb-master.sql"
SYSTEM_MASTER_FILE="${SCRIPT_DIR}/seed/dtb-system-master.sql"

HOST=""
PORT="3306"
USER=""
PASS=""
DB=""

die() { echo "setup-db: $*" >&2; exit 1; }

usage() {
    sed -n '2,30p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
    exit "${1:-0}"
}

# Parse a DATABASE_URL of the form
#   scheme://user:pass@host:port/dbname?query
parse_url() {
    local url="$1"
    # strip scheme
    local rest="${url#*://}"
    # strip query string
    rest="${rest%%\?*}"
    # userinfo @ hostport / db
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
    # URL-decode the (commonly encoded) password / user
    USER="$(printf '%b' "${USER//%/\\x}")"
    PASS="$(printf '%b' "${PASS//%/\\x}")"
}

# --- argument parsing -------------------------------------------------------
if [[ $# -eq 0 ]]; then
    [[ -n "${DATABASE_URL:-}" ]] || die "no arguments and DATABASE_URL is unset (see --help)"
    parse_url "$DATABASE_URL"
elif [[ $# -eq 1 && "$1" != --* ]]; then
    parse_url "$1"
else
    while [[ $# -gt 0 ]]; do
        case "$1" in
            -h|--help)  usage 0 ;;
            --url)      parse_url "$2"; shift 2 ;;
            --host)     HOST="$2"; shift 2 ;;
            --port)     PORT="$2"; shift 2 ;;
            --user)     USER="$2"; shift 2 ;;
            --pass)     PASS="$2"; shift 2 ;;
            --db)       DB="$2";   shift 2 ;;
            *)          die "unknown argument: $1 (see --help)" ;;
        esac
    done
fi

# --- validation -------------------------------------------------------------
[[ -n "$HOST" ]] || die "host is required"
[[ -n "$USER" ]] || die "user is required"
[[ -n "$DB"   ]] || die "database name is required"
[[ -r "$SCHEMA_FILE" ]] || die "schema file missing/unreadable: $SCHEMA_FILE"
[[ -r "$SEED_FILE"   ]] || die "seed file missing/unreadable: $SEED_FILE"
[[ -r "$SYSTEM_MASTER_FILE" ]] || die "system master seed missing/unreadable: $SYSTEM_MASTER_FILE"
command -v mysql >/dev/null 2>&1 || die "mysql client not found on PATH"

# mysql client invocation; password passed via MYSQL_PWD to keep it off argv.
# --default-character-set=utf8mb4 forces the connection charset so schema/seed
# loads never depend on the client's default (which may be latin1 and would
# corrupt Japanese data). utf8mb4 is the project default everywhere.
mysql_run() {
    MYSQL_PWD="$PASS" mysql --default-character-set=utf8mb4 -h "$HOST" -P "$PORT" -u "$USER" "$@"
}

echo "setup-db: target  = ${USER}@${HOST}:${PORT}/${DB}"
echo "setup-db: schema  = ${SCHEMA_FILE}"
echo "setup-db: migrate = ${MIGRATIONS_DIR}"
echo "setup-db: seed    = ${SEED_FILE}"
echo "setup-db: system  = ${SYSTEM_MASTER_FILE}"

# --- 1. (re)create database -------------------------------------------------
# DROP + CREATE so the run is idempotent: the schema uses bare `CREATE TABLE`,
# so a re-run against an existing schema would otherwise fail with "table
# already exists". A fresh database is the clean reload path.
echo "setup-db: [1/5] (re)creating database '${DB}' ..."
mysql_run -e "DROP DATABASE IF EXISTS \`${DB}\`;"
mysql_run -e "CREATE DATABASE \`${DB}\` \
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;"

# --- 2. load schema (FK checks off) -----------------------------------------
echo "setup-db: [2/5] loading schema (FOREIGN_KEY_CHECKS off) ..."
{
    echo "SET FOREIGN_KEY_CHECKS=0;"
    cat "$SCHEMA_FILE"
    echo "SET FOREIGN_KEY_CHECKS=1;"
} | mysql_run "$DB"

# --- 3. apply BeMart schema migrations --------------------------------------
echo "setup-db: [3/5] applying BeMart schema migrations ..."
MIGRATION_FILES=()
if [[ -d "$MIGRATIONS_DIR" ]]; then
    for file in "$MIGRATIONS_DIR"/*.sql; do
        [[ -e "$file" ]] || continue
        MIGRATION_FILES+=("$file")
    done
fi

if [[ ${#MIGRATION_FILES[@]} -eq 0 ]]; then
    echo "setup-db:       (no migrations)"
else
    for file in "${MIGRATION_FILES[@]}"; do
        echo "setup-db:       $(basename "$file")"
        mysql_run "$DB" < "$file"
    done
fi

# --- 4. load mtb_* master seed ----------------------------------------------
echo "setup-db: [4/5] loading mtb_* master seed ..."
mysql_run "$DB" < "$SEED_FILE"

# --- 5. load dtb_* system master rows ----------------------------------------
echo "setup-db: [5/5] loading dtb_* system master rows ..."
mysql_run "$DB" < "$SYSTEM_MASTER_FILE"

# --- summary ----------------------------------------------------------------
# Exact COUNT(*) per mtb_* table (information_schema.table_rows is only an
# estimate for InnoDB, so build a UNION ALL of real counts).
echo "setup-db: done. mtb_* row counts (exact):"
COUNT_SQL="$(mysql_run -N "$DB" -e "
    SELECT GROUP_CONCAT(
        CONCAT(\"SELECT '\", table_name, \"' AS t, COUNT(*) AS n FROM \`\",
               table_name, \"\`\")
        SEPARATOR ' UNION ALL ')
    FROM information_schema.tables
    WHERE table_schema = '${DB}' AND table_name LIKE 'mtb\_%';" 2>/dev/null || true)"
if [[ -n "$COUNT_SQL" && "$COUNT_SQL" != "NULL" ]]; then
    mysql_run -N "$DB" -e "$COUNT_SQL ORDER BY t;" \
        | awk '{ printf "  %-30s %s\n", $1, $2 }'
else
    echo "  (row-count summary unavailable; setup itself succeeded)"
fi

echo "setup-db: production database '${DB}' is ready."
echo "setup-db: NOTE — dtb_* operational data (customers, orders, products)"
echo "setup-db:        is migrated separately and is not loaded by this script;"
echo "setup-db:        only dtb_* system master rows are loaded here."
