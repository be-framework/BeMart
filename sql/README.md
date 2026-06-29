# sql/ — EC-CUBE 4.3 SQL artefacts and BeMart SQL test framework

Phase 2 inputs that drive the EC-CUBE → BEAR.Sunday + Be Framework migration.

## Layout

```
sql/
├── schema/                              # source-of-truth BeMart schema
│   └── bemart-schema.sql                # 65 tables, authored from first principles, utf8mb4_bin
├── migrations/                          # BeMart schema deltas applied after the base schema
├── seed/                                # committed reference/master data
│   ├── mtb-master.sql                   # 22 mtb_* tables, 395 reference rows
│   └── dtb-system-master.sql            # installer-level dtb_* system rows
├── diff/                                # planning docs
│   └── entity-vs-eccube.md              # BeMart Entity ↔ EC-CUBE table diff (Phase 2b)
├── setup-db.sh                          # reproducible prod DB bring-up
└── README.md
```

## Production database bring-up

A live production database needs four committed artefact sets: the **schema**
(`schema/bemart-schema.sql`), BeMart **migrations**
(`migrations/*.sql`), the **mtb_\* master seed** (`seed/mtb-master.sql`),
and the **dtb_\* system master seed** (`seed/dtb-system-master.sql`).
`setup-db.sh` stitches them together so a prod DB can be stood up
reproducibly:

```bash
# from a DATABASE_URL (Symfony/Doctrine style)
sql/setup-db.sh 'mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4'

# or from explicit args
sql/setup-db.sh --host 127.0.0.1 --port 3306 \
                --user root --pass '' --db eccubedb

# or from the DATABASE_URL environment variable
DATABASE_URL='mysql://...' sql/setup-db.sh
```

The script:

1. **DROPs + CREATEs** the target database (`utf8mb4` / `utf8mb4_bin`,
   matching the dump). It is idempotent — re-running gives the same result.
   The DROP is required because the schema dump uses bare `CREATE TABLE`
   (not `CREATE TABLE IF NOT EXISTS`). **Warning:** any existing data in the
   target database is destroyed; never point it at a populated prod DB —
   use it to *bring up* a fresh one.
2. Loads the schema **wrapped in `SET FOREIGN_KEY_CHECKS=0/1`**. The schema
   carries cross-table FKs, so we disable FK checks during load to allow any
   table ordering. This mirrors the workaround in `be/tests/Sql/bootstrap.php`.
3. Applies BeMart schema deltas under `migrations/*.sql` in filename order.
4. Loads `seed/mtb-master.sql`.
5. Loads `seed/dtb-system-master.sql`: installer-level rows such as the
   default admin member, layout, mail template, and initial payment methods.
6. Prints exact `COUNT(*)` per `mtb_*` table as a sanity check
   (e.g. `mtb_pref = 47`).

After this, the database has the full schema, reference data, and the small
set of installer system rows required for a fresh shop to run. Migrating or
seeding `dtb_*` customer/order/product/cart/favorite business data from a live
EC-CUBE instance is a **separate operational concern** and is **not** performed
by this script.

## The dtb_* system master seed (`seed/dtb-system-master.sql`)

These rows are application configuration masters, not business fixtures. They
exist so a freshly installed shop has the same minimum affordances the web
workflow expects:

- `dtb_member`: the test/admin bootstrap account.
- `dtb_payment`: initial visible payment methods (`代金引換`,
  `クレジットカード`) referenced by checkout `dtb_order.payment_id`.
- `dtb_layout`: the default PC layout.
- `dtb_mail_template`: the default order mail template.

Do not add products, customers, carts, orders, shippings, favorites, or other
workflow-created business state here. Those must be created through Web/HTTP
affordances in workflow tests.

## The mtb_* master seed (`seed/mtb-master.sql`)

`mtb_*` tables are EC-CUBE's reference/enum data — prefectures, order
statuses, sale types, sexes, jobs, csv types, authorities, countries,
device types, rounding/tax types, work flags, etc. This is the seedable,
version-controllable part of the database (master data, no PII), so it is
committed.

- **Source:** EC-CUBE 4.3's installer fixtures —
  `src/Eccube/Resource/doctrine/import_csv/ja/mtb_*.csv` (the same CSVs
  EC-CUBE's installer / `bin/console eccube:fixtures:load` read). The
  EC-CUBE source is cloned at `tools/ec-cube-source/` (gitignored).
- **Coverage:** all 22 `mtb_*` tables in the schema, 395 reference rows
  total. (`definition.yml` also lists `mtb_shipping_status`, but EC-CUBE 4.3
  ships no such table or CSV — it is a stale entry and is correctly absent.)
- **Idempotent:** each table is `TRUNCATE`d then re-`INSERT`ed, wrapped in
  `SET FOREIGN_KEY_CHECKS=0/1` (mtb_* rows are FK targets of dtb_* tables).
  Applying the seed alone, or re-running `setup-db.sh`, is safe.

To regenerate the seed after an EC-CUBE version bump, re-extract from the
updated CSVs under `tools/ec-cube-source/.../import_csv/ja/`.

## Workflow — ALPS-first

`alps.json` is the project's source of truth (Phase 2a Step 5 retrofit
corrected an earlier order that started from the SQL impl):

1. **ALPS first.** Add or amend the descriptor (`src-entity` tag for
   Entity rows) before writing the SQL. Discoveries made while writing
   the impl (e.g. the 2-axis `class_category_id1` / `class_category_id2`
   finding on `dtb_product_class`) must be written back into `alps.json`.
2. **SQL impl** under `be/src/Reason/Query/Sql*.php` — pure prepared
   statements, no Doctrine.
3. **Unit test** — `be/tests/Sql/Sql<Foo>Test.php`, asserts storage contract.
4. **Final-direct integration** — `be/tests/Sql/*SqlIntegrationTest.php`,
   SQL classes wired into a Final's constructor manually. Fast, no Injector.
5. **Hypermedia test** — `tests/Resource/Sql/*ResourceSqlTest.php`,
   end-to-end via `ResourceInterface::get(...)` through the full Becoming chain.
6. **ALPS tagging** — verify the Entity descriptor carries `src-entity` and
   refers to the right fields.

## Running the SQL test suites

```bash
composer test:sql                            # Resource-layer hypermedia (sql testsuite)
vendor/bin/phpunit --testsuite sql           # same, long form
vendor/bin/phpunit tests/Resource/Sql/       # explicit directory form
vendor/bin/phpunit                           # everything (~765 tests)
```

The `sql` suite runs `be/tests/Sql/bootstrap.php` on first use, which drops
+ recreates `eccubedb_test` and loads `bemart-schema.sql` with FK checks
disabled. Each test runs inside a transaction that `tearDown` rolls back.

If `DATABASE_URL` is unset the SQL suites skip cleanly. If it is set but
the server is unreachable, the suite fails fast (no silent skips).

The top-level `phpunit.xml` wires the default `DATABASE_URL`:

```text
mysql://root@127.0.0.1:3306/eccubedb_test?charset=utf8mb4&serverVersion=8.0.0
```

## Setting up the local DB with malt

The dev environment uses `malt` for the local DB. The checked-in
`malt.json` starts MySQL 8.0 on port 3306, which is the target baseline.
The local development connection is `root` with no password; do not create
or grant a separate `dbuser` account for normal local runs.

```bash
malt start
source <(malt env)
export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb_test?charset=utf8mb4&serverVersion=8.0.0'
sql/setup-db.sh "$DATABASE_URL"

/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit --testsuite sql --colors=never
```

Defaults: host `127.0.0.1`, port `3306`, user `root`, password `(none)`.

## SQL implementations landed so far

Every Sql class lives under `be/src/Reason/Query/Sql*.php`. **Production
DI is still bound to the Fakes**; Sql impls are wired into tests via the
override pattern below until Phase 2b flips the bindings.

| Class | Backed by | Notes |
|---|---|---|
| `SqlCustomerQuery` (Step 2) | `dtb_customer` | Grade A 1:1 mapping |
| `SqlOrderQuery` (Step 3) | `dtb_order` + `dtb_order_item` | Excludes `order_status_id = 8` (PROCESSING) from finalized reads; `byPreOrderId` is the lone PROCESSING reader |
| `SqlFavoriteStorage` (Step 3) | `dtb_customer_favorite_product` + `dtb_product` + `dtb_product_class` | 3-way JOIN; uses `class_category_id1/2 IS NULL` to pick the "default" product_class for `product_code` + `price02` |
| `SqlCartQuery` (Step 4) | `dtb_cart` + `dtb_cart_item` + `dtb_product_class` | saleTypeId parsed from `cart_key` suffix (the schema has no column); items JOIN through product_class for `product_code` |
| `SqlCartCommand` (Step 4) | `dtb_cart` + `dtb_cart_item` | Upsert by DELETE-then-INSERT inside a SAVEPOINT (transaction-nesting safe); `clearByPreOrderId` + `clearBySessionPrefix` for the checkout-completed and withdraw flows |

## Test patterns

Three test surfaces, picked per scope:

| Path | Surface | Purpose |
|---|---|---|
| `be/tests/Sql/Sql*Test.php` | Storage unit | One Sql class, no Final, no Injector. |
| `be/tests/Sql/*SqlIntegrationTest.php` | Final-direct integration | Sql class(es) wired manually into a Final's constructor — no Injector boot, fastest end-to-end. |
| `tests/Resource/Sql/*ResourceSqlTest.php` | Resource hypermedia | `ResourceInterface::get(...)` end-to-end through the AppModule with Sql impls overlaid. |

Hypermedia tests share `tests/Resource/Sql/AbstractResourceSqlTestCase`,
which rebinds (production-Fake → test-Sql):

- `PDO::class` → shared test PDO singleton (per-test transaction)
- `CustomerQueryInterface` → `SqlCustomerQuery`
- `OrderQueryInterface` → `SqlOrderQuery`
- `FavoriteStorageInterface` → `SqlFavoriteStorage`
- `CartQueryInterface` → `SqlCartQuery`
- `CartCommandInterface` → `SqlCartCommand`

Session interfaces (`SessionInterface` / `AdminSessionInterface`) are
NOT rebound — admin/customer sessions stay in-memory by design (the
production cookie/JWT adapter is deferred). Subclasses layer their
own Fake session via the `extraOverride()` hook.

Fixture helpers live in `be/tests/Sql/SqlFixturesTrait` (shared by both
base classes): `insertCustomer` / `Product` / `Order` / `OrderItem` /
`Cart` / `CartItem` / `Favorite`, plus `defaultProductClassId`.

## Phase 2b planning

See [`diff/entity-vs-eccube.md`](diff/entity-vs-eccube.md) for the
Entity ↔ table mapping (8 grade-A 1:1, 8 grade-B JOINs, 5 grade-C
schema deltas). Phase 2b will flip production bindings + add the
remaining `Sql*` classes on the same framework.

## SQL quality analysis (on-demand)

[Koriym.SqlQuality](https://github.com/koriym/Koriym.SqlQuality) runs
`EXPLAIN FORMAT=JSON` / `EXPLAIN ANALYZE` against a **live MySQL** and flags
performance anti-patterns (FullTableScan, IneffectiveJoin,
FunctionInvalidatesIndex, ImplicitTypeConversion, …). It is wired as a
**standalone, on-demand** tool — deliberately **not** part of `composer sa`,
`composer test`, `composer build`, or CI. Run it by hand when reviewing query
shape; it requires a live DB and so is gated the same way the SQL test suites
are.

Scope: the **76 single-statement SELECT (read) queries** in `var/sql/`.
Command files (INSERT/UPDATE/DELETE) and multi-statement scripts
(`cart_save`, `order_item_register`, `plugin_set_enabled`,
`tmail_template_update`) are excluded — they cannot be EXPLAIN-analyzed and
would execute writes under `EXPLAIN ANALYZE`. The scope is fixed by the keys
of [`var/sql-quality/sql_params.php`](../var/sql-quality/sql_params.php), which
also supplies each query's bind values.

### Prerequisites

1. Bring up the DB and load schema + master + a representative dataset:

   ```bash
   malt start
   sql/setup-db.sh "mysql://root@127.0.0.1:3306/eccubedb_test?charset=utf8mb4"
   mysql -h127.0.0.1 -P3306 -uroot eccubedb_test < sql/seed/analysis-sample.sql
   ```

   `sql/seed/analysis-sample.sql` bulk-generates a representative catalog
   (2,000 products / product_classes, 500 customers, 5 admins) so EXPLAIN sees
   non-zero cardinality and produces realistic access paths. Order / cart /
   config tables are left empty — their SELECTs still EXPLAIN validly (0 rows).

2. **macOS / Homebrew note** — if `malt start` leaves MySQL `stopped` with a
   `Library not loaded: …libprotobuf-lite.34.1.0.dylib` error, the Homebrew
   `mysql@8.0` keg is linked against an older protobuf than the one currently
   on `protobuf`. Either `brew reinstall mysql@8.0`, or launch `mysqld`
   directly with the older keg on the loader path (reversible, no reinstall):

   ```bash
   DYLD_FALLBACK_LIBRARY_PATH=/opt/homebrew/Cellar/protobuf/34.1/lib \
     /opt/homebrew/opt/mysql@8.0/bin/mysqld \
     --defaults-file=malt/conf/my_3306.cnf.tmp \
     --datadir=malt/var/mysql_0 --socket=malt/tmp/mysql_3306.sock --port=3306 &
   disown
   ```

   (`mysqld_safe` / `nohup` won't work: they run under SIP-protected binaries
   that strip `DYLD_*`. Launch `mysqld` directly.)

### Run

```bash
composer sql:quality
```

Reports are written to `build/sql-quality/` (git-ignored):

- `summary_report.md` — one row per query with Cost, Exec Time, Level
  (μ ± σ classification), and detected Issues.
- `<query>.md` — per-query EXPLAIN detail plus an AI optimization prompt.

### Notes

- Values in `sql_params.php` target the seed data so `EXPLAIN ANALYZE` returns
  rows; placeholders only need a valid type, so queries against unseeded tables
  still analyze (returning 0 rows).
- The analyzer skips any query it cannot EXPLAIN and continues — such skips
  are a useful portability signal. All queries in `var/sql/` use
  `GROUP_CONCAT(JSON_OBJECT(...) ORDER BY ...)` which is supported by MySQL 8.0.
