# sql/ — EC-CUBE 4.3 SQL artefacts and BeMart SQL test framework

Phase 2 inputs that drive the EC-CUBE → BEAR.Sunday + Be Framework migration.

## Layout

```
sql/
├── schema/                              # source-of-truth EC-CUBE 4.3 schema
│   └── ec-cube-4.3-mysql-mysqldump.sql  # 65 tables, structure only, utf8mb4_bin
├── diff/                                # planning docs
│   └── entity-vs-eccube.md              # BeMart Entity ↔ EC-CUBE table diff (Phase 2b)
└── README.md
```

Future (Phase 2b+): `sql/migrations/` (schema deltas), `sql/fixtures/` (seed data).

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
