# sql/ — EC-CUBE 4.3 SQL artefacts and BeMart SQL test framework

Phase 2 inputs that drive the EC-CUBE → BEAR.Sunday + Be Framework migration.

## Layout

```
sql/
├── schema/                              # source-of-truth EC-CUBE 4.3 schema
│   └── ec-cube-4.3-mysql-mysqldump.sql  # 65 tables, structure only, utf8mb4_bin
├── seed/                                # committed reference/master data
│   └── mtb-master.sql                   # 22 mtb_* tables, 395 reference rows
├── diff/                                # planning docs
│   └── entity-vs-eccube.md              # BeMart Entity ↔ EC-CUBE table diff (Phase 2b)
├── setup-db.sh                          # reproducible prod DB bring-up
└── README.md
```

Future (Phase 2b+): `sql/migrations/` (schema deltas).

## Production database bring-up

A live production database needs two committed artefacts: the **schema**
(`schema/ec-cube-4.3-mysql-mysqldump.sql`) and the **mtb_\* master seed**
(`seed/mtb-master.sql`). `setup-db.sh` stitches them together so a prod DB
can be stood up reproducibly:

```bash
# from a DATABASE_URL (Symfony/Doctrine style)
sql/setup-db.sh 'mysql://dbuser:secret@127.0.0.1:3306/eccubedb?charset=utf8mb4'

# or from explicit args
sql/setup-db.sh --host 127.0.0.1 --port 3306 \
                --user dbuser --pass secret --db eccubedb

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
2. Loads the schema **wrapped in `SET FOREIGN_KEY_CHECKS=0/1`**. The dump
   carries cross-table FKs but no such pragma, so a plain sequential load
   trips on the first table (`dtb_authority_role` → `dtb_member`). This
   mirrors the workaround in `be/tests/Sql/bootstrap.php` (Phase 2a Step 2).
3. Loads `seed/mtb-master.sql`.
4. Prints exact `COUNT(*)` per `mtb_*` table as a sanity check
   (e.g. `mtb_pref = 47`).

After this, the database has the full schema + all reference data and is
ready for `dtb_*` operational data. Migrating `dtb_*` customer/order/product
data from a live EC-CUBE instance is a **separate operational concern** and
is **not** performed by this script.

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
vendor/bin/phpunit --testsuite bemart-sql    # storage + Final-direct
vendor/bin/phpunit tests/Resource/Sql/       # Resource-layer hypermedia
vendor/bin/phpunit                           # everything (~765 tests)
```

The `bemart-sql` suite drops + recreates `eccubedb_test` on every run
(schema loaded with FK checks disabled). Each test runs inside a
transaction `tearDown` rolls back.

If `DATABASE_URL` is unset the SQL suites skip cleanly. If it is set but
the server is unreachable, the suite fails fast (no silent skips).

The top-level `phpunit.xml` wires the default `DATABASE_URL`:

```
mysql://dbuser:secret@127.0.0.1:3306/eccubedb_test?charset=utf8mb4&serverVersion=mariadb-10.11.14
```

## Setting up MariaDB locally

The dev environment uses MariaDB 10.11 with a `dbuser` account.

```bash
sudo service mariadb start

# One-time grant for the test DB (CI image already has this):
sudo mysql -e "GRANT ALL PRIVILEGES ON \`eccubedb_test\`.* TO 'dbuser'@'127.0.0.1';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

Defaults: host `127.0.0.1`, port `3306`, user `dbuser`, password `secret`.

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
