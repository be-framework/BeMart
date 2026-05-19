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

## Running the SQL test suite

```bash
vendor/bin/phpunit --testsuite bemart-sql
```

The suite spins up an `eccubedb_test` database from scratch on every run
(`DROP DATABASE` + `CREATE DATABASE` + load the schema with FK checks
disabled). Each test runs inside a transaction that `tearDown` rolls
back, so tests can mutate the DB without affecting one another.

If `DATABASE_URL` is unset the suite skips cleanly. If it is set but the
server is unreachable, the suite fails fast — silent skips would defeat
the smoke-test purpose.

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

Phase 2a is bootstrapping the SQL backend incrementally — every new
class lives under `be/src/Reason/Query/Sql*.php` and is tested via the
`bemart-sql` testsuite. **Production DI is still bound to the Fakes**;
each SQL class is wired manually in tests until Phase 2b flips the
bindings.

| Class | Backed by | Notes |
|---|---|---|
| `SqlCustomerQuery` (Step 2) | `dtb_customer` | Grade A 1:1 mapping |
| `SqlOrderQuery` (Step 3) | `dtb_order` + `dtb_order_item` | Excludes `order_status_id = 8` (PROCESSING) from finalized reads; `byPreOrderId` is the lone PROCESSING reader |
| `SqlFavoriteStorage` (Step 3) | `dtb_customer_favorite_product` + `dtb_product` + `dtb_product_class` | 3-way JOIN; uses `class_category_id1/2 IS NULL` to pick the "default" product_class for `product_code` + `price02` |

## Integration test pattern

End-to-end smokes wire the SQL implementations directly into a
Final-under-test's constructor (no injector, no Becoming chain). The
AdminSession is substituted with the in-process `FakeAdminSession`
because the production cookie/JWT adapter is deferred. See
[`be/tests/Sql/AdminCustomerFetchedSqlIntegrationTest.php`](../be/tests/Sql/AdminCustomerFetchedSqlIntegrationTest.php)
for the canonical shape (3 SQL backends + a test-fake AdminSession,
asserting projection counts + projection shape + AUTHZ/404 ladder).

## Phase 2b planning

See [`diff/entity-vs-eccube.md`](diff/entity-vs-eccube.md) for the
table-by-table mapping of every BeMart Entity against `dtb_*` / `mtb_*`
columns, including 8 grade-A 1:1 matches, 8 grade-B (joins needed), and
5 grade-C (schema deltas required). Phase 2b will implement the
remaining `Sql*` query/storage classes on top of the same test
framework introduced by Phase 2a Step 2 (`SqlCustomerQuery`).
