<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\SqlAddressStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlBaseInfoStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlBlockStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCartCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlCartQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlCategoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlClassCategoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlClassNameStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCsvColumnConfigStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlEmailUniquenessChecker;
use MyVendor\BeMart\Be\Reason\Query\SqlFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlLayoutStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlLoginHistoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlNewsStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlPageStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPasswordResetTokenStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPaymentMethodAdminStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPluginStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlShippingAddressStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTagStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTaxRuleStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTemplateStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTradeLawStorage;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AddressIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\BlockIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CategoryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCategoryIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassNameIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\NewsIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PageIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodAdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\SqlAddressIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlAdminIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlBlockIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlClassCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlClassNameIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlCustomerIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlNewsIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlPageIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlPaymentMethodAdminIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlTagIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlTaxRuleIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\TagIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\TaxRuleIdGeneratorInterface;
use MyVendor\BeMart\Be\Tests\Sql\SqlFixturesTrait;
use MyVendor\BeMart\Module\AppModule;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;
use RuntimeException;

use function dirname;

/**
 * Base class for SQL-backed Resource-layer hypermedia tests
 * (Phase 2a Step 5).
 *
 * Combines two existing patterns:
 *
 *   - The Injector + `AppModule->override(...)` pattern used by every
 *     `tests/Resource/*ResourceTest.php` (e.g. CartResourceTest,
 *     AdminCustomerResourceTest, FavoriteListResourceTest). The
 *     Resource client is built via `$injector->getInstance(ResourceInterface::class)`
 *     so the full Becoming chain runs end-to-end.
 *
 *   - The DB lifecycle of {@see \MyVendor\BeMart\Be\Tests\Sql\AbstractSqlTestCase}
 *     (drops + recreates `eccubedb_test` on first call, per-test
 *     transaction that tearDown rolls back). The bootstrap is the same
 *     file under `be/tests/Sql/bootstrap.php`, sharing a single PDO
 *     singleton across all SQL tests in a PHPUnit run.
 *
 * Critical contract — `PDO::class` binding:
 *   AppModule does not bind PDO (production DI is still Fake-based). The
 *   override module below binds the SHARED test PDO singleton to `PDO::class`
 *   so every Sql* class injected via `to(SqlFoo::class)` resolves the
 *   SAME connection — the test fixtures' INSERTs and the production
 *   Becoming chain's SELECTs operate on the same in-memory transaction
 *   that tearDown rolls back.
 *
 * Rebound interfaces (production-Fake → test-Sql):
 *   - CustomerQueryInterface       → SqlCustomerQuery
 *   - CustomerCommandInterface     → SqlCustomerCommand (Phase 2b —
 *       write side of dtb_customer: register / activate / update /
 *       updatePassword. Mirrors SqlCustomerQuery's column↔field map
 *       so a read-after-write round-trips. secret_key is NOT NULL
 *       UNIQUE — register synthesises a unique token when the entity
 *       carries a null; activate keeps the key rather than nulling it)
 *   - CustomerIdGeneratorInterface → SqlCustomerIdGenerator (Phase 2b —
 *       CustomerRegistering needs a numeric id pre-allocated so
 *       SqlCustomerCommand::register can persist with an explicit PK;
 *       the Fake generator emits 32-char hex that the SQL impl rejects
 *       as non-numeric, same shape as the Admin generator pairing)
 *   - EmailUniquenessCheckerInterface → SqlEmailUniquenessChecker
 *       (Phase 2b — registration / profile-update duplicate-email
 *       guard, a trivial existence probe against dtb_customer.email,
 *       the natural read-guard companion of the customer write side)
 *   - OrderQueryInterface          → SqlOrderQuery
 *   - OrderCommandInterface        → SqlOrderCommand (Phase 2b — write
 *       side of dtb_order: register / update / updateStatus. register
 *       is an UPSERT keyed by pre_order_id — it PROMOTES an existing
 *       pre-order row (PROCESSING→NEW, EC-CUBE's mutate-the-same-row
 *       checkout semantics) and only INSERTs when no pre-order exists
 *       (admin data-entry order). Mirrors SqlOrderQuery's column↔field
 *       map so a read-after-write round-trips. order_status_id has no
 *       FK constraint so no master seeding is needed; customer_id is an
 *       int FK — a non-numeric BeMart handle writes NULL)
 *   - FavoriteStorageInterface     → SqlFavoriteStorage
 *   - CartQueryInterface           → SqlCartQuery
 *   - CartCommandInterface         → SqlCartCommand
 *   - AddressStorageInterface      → SqlAddressStorage  (Phase 2b)
 *   - AddressIdGeneratorInterface  → SqlAddressIdGenerator (Phase 2b —
 *       CustomerAddressCreated needs a numeric id pre-allocated so
 *       SqlAddressStorage can persist with that explicit PK)
 *   - TagStorageInterface          → SqlTagStorage  (Phase 2b)
 *   - TagIdGeneratorInterface      → SqlTagIdGenerator (Phase 2b —
 *       TagCreated needs a numeric id pre-allocated so SqlTagStorage
 *       can persist with that explicit PK; the Fake generator emits
 *       a `tg-` prefix that the SQL impl rejects as non-numeric)
 *   - BaseInfoStorageInterface     → SqlBaseInfoStorage (Phase 2b —
 *       singleton row at id=1; the SQL impl returns installer-default
 *       fields when the row is missing so the hypermedia contract is
 *       identical to the Fake-backed baseline with no extra fixture
 *       setup required)
 *   - TaxRuleStorageInterface      → SqlTaxRuleStorage  (Phase 2b)
 *   - TaxRuleIdGeneratorInterface  → SqlTaxRuleIdGenerator (Phase 2b —
 *       TaxRuleCreated needs a numeric id pre-allocated so
 *       SqlTaxRuleStorage can persist with that explicit PK; the
 *       Fake generator emits hex that the SQL impl rejects as
 *       non-numeric, same shape as the Tag generator pairing)
 *   - NewsStorageInterface         → SqlNewsStorage  (Phase 2b)
 *   - NewsIdGeneratorInterface     → SqlNewsIdGenerator (Phase 2b —
 *       NewsCreated needs a numeric id pre-allocated so SqlNewsStorage
 *       can persist with that explicit PK; the Fake generator emits an
 *       `nw-` prefix that the SQL impl rejects as non-numeric, same
 *       shape as the Tag / TaxRule generator pairings)
 *   - PageStorageInterface         → SqlPageStorage  (Phase 2b)
 *   - PageIdGeneratorInterface     → SqlPageIdGenerator (Phase 2b —
 *       PageCreated needs a numeric id pre-allocated so SqlPageStorage
 *       can persist with that explicit PK; the Fake generator emits a
 *       `pg-` prefix that the SQL impl rejects as non-numeric, same
 *       shape as the Tag / News / TaxRule generator pairings)
 *   - BlockStorageInterface        → SqlBlockStorage  (Phase 2b)
 *   - BlockIdGeneratorInterface    → SqlBlockIdGenerator (Phase 2b —
 *       BlockCreated needs a numeric id pre-allocated so SqlBlockStorage
 *       can persist with that explicit PK; the Fake generator emits a
 *       `bk-` prefix that the SQL impl rejects as non-numeric, same
 *       shape as the Page / Tag / News / TaxRule generator pairings)
 *   - CategoryStorageInterface     → SqlCategoryStorage  (Phase 2b)
 *   - CategoryIdGeneratorInterface → SqlCategoryIdGenerator (Phase 2b —
 *       CategoryCreated needs a numeric id pre-allocated so
 *       SqlCategoryStorage can persist with that explicit PK; the Fake
 *       generator emits 32-char hex that the SQL impl rejects as
 *       non-numeric, same shape as the Block / Page / Tag / News /
 *       TaxRule generator pairings)
 *   - ClassNameStorageInterface    → SqlClassNameStorage  (Phase 2b —
 *       product-variation AXIS, dtb_class_name; remove pre-clears child
 *       dtb_class_category rows to avoid FK 1451, same shape as the
 *       Category → dtb_product_category cascade)
 *   - ClassNameIdGeneratorInterface → SqlClassNameIdGenerator (Phase 2b —
 *       the ClassName-create Final needs a numeric id pre-allocated so
 *       SqlClassNameStorage can persist with that explicit PK; the Fake
 *       generator emits 32-char hex that the SQL impl rejects as
 *       non-numeric, same shape as the Category generator pairing)
 *   - ClassCategoryStorageInterface → SqlClassCategoryStorage (Phase 2b —
 *       product-variation VALUE under a ClassName axis, dtb_class_category;
 *       every row is pinned to a parent dtb_class_name via the
 *       class_name_id FK. remove issues a plain DELETE — unlike
 *       SqlClassNameStorage it does NOT pre-clear children: deleting a
 *       single variant value must not cascade-delete the dtb_product_class
 *       rows that use it)
 *   - ClassCategoryIdGeneratorInterface → SqlClassCategoryIdGenerator
 *       (Phase 2b — the ClassCategory-create Final needs a numeric id
 *       pre-allocated so SqlClassCategoryStorage can persist with that
 *       explicit PK; the Fake generator emits 32-char hex that the SQL
 *       impl rejects as non-numeric, same shape as the ClassName /
 *       Category generator pairings)
 *   - LayoutStorageInterface       → SqlLayoutStorage (Phase 2b — admin
 *       CMS layout list + update against dtb_layout. The interface has
 *       only list / getById / put — no create, no delete affordance per
 *       ALPS — so there is NO LayoutIdGenerator pairing: layoutIds are
 *       never minted by the BeMart slice, only read back from rows the
 *       installer/fixture seeded. SqlLayoutStorage rejects a non-numeric
 *       id as a miss, so `nonexistent` folds to a 404 on both backends)
 *   - TemplateStorageInterface     → SqlTemplateStorage (Phase 2b — admin
 *       design-template registry list against dtb_template. The
 *       interface is `list()` only — ALPS exposes a single affordance
 *       (`goTemplateList`), no create / update / delete, no upload flow
 *       — so there is NO TemplateIdGenerator and no getById / put /
 *       remove. Templates are filesystem-backed in EC-CUBE; dtb_template
 *       is only the installed-flavour registry, read-only from this
 *       slice. Same column shape as dtb_layout)
 *   - LoginHistoryStorageInterface → SqlLoginHistoryStorage (Phase 2b —
 *       admin login-attempt audit log against dtb_login_history. The
 *       interface is listRecent + append — an append + list audit log,
 *       no getById / update / delete (an audit row has no
 *       client-meaningful handle and is never mutated), so there is NO
 *       LoginHistoryIdGenerator. login_history_status_id is a NOT NULL
 *       FK to the empty mtb_login_history_status master — seeded via
 *       seedLoginHistoryStatus, same precedent as seedAdminMasters)
 *   - PaymentMethodAdminStorageInterface → SqlPaymentMethodAdminStorage
 *       (Phase 2b — admin payment-method master CRUD against
 *       dtb_payment. list / getById / put / remove; remove pre-clears
 *       child dtb_payment_option link rows to avoid FK 1451, same shape
 *       as the Block → dtb_block_position cascade)
 *   - PaymentMethodAdminIdGeneratorInterface → SqlPaymentMethodAdminIdGenerator
 *       (Phase 2b — PaymentMethodAdminCreated needs a numeric id
 *       pre-allocated so SqlPaymentMethodAdminStorage can persist with
 *       that explicit PK; the Fake generator emits 32-char hex that the
 *       SQL impl rejects as non-numeric, same shape as the Block / Tag /
 *       News generator pairings)
 *   - TradeLawStorageInterface     → SqlTradeLawStorage (Phase 2b — the
 *       特定商取引法 page. EC-CUBE models it as up to 15 per-item rows;
 *       the Wave-8 interface is single-blob — get() returns the whole
 *       page body, update() replaces it — so SqlTradeLawStorage stores
 *       the blob in ONE carrier row's description column at
 *       dtb_tradelaw.id=1, the same singleton-row shape SqlBaseInfoStorage
 *       uses for dtb_base_info.id=1. No generator: the row identity is
 *       fixed, never minted. get() falls back to FakeTradeLawStorage's
 *       installer-default body when the carrier row is absent so the
 *       hypermedia contract is identical to the Fake-backed baseline
 *       with no extra fixture setup required)
 *   - ShippingAddressStorageInterface → SqlShippingAddressStorage
 *       (Phase 2b — per-order delivery target against dtb_shipping.
 *       getByOrderNo / put / listAll, all keyed by the customer-facing
 *       orderNo. dtb_shipping references the order via the int FK
 *       order_id, so every method resolves order_no → dtb_order.id (a
 *       sub-SELECT for reads, a JOIN for listAll). put enforces the
 *       single-row-per-order invariant by probing order_id and
 *       UPDATEing in place — dtb_shipping has no UNIQUE on order_id.
 *       No generator: the row identity is the order_id, never minted.
 *       pref_id is a nullable FK to the empty mtb_pref master — pref=0
 *       writes NULL, NULL reads back as 0)
 *   - ProductClassQueryInterface   → SqlProductClassQuery (Phase 2b —
 *       the per-variation SKU lookup against dtb_product_class, keyed by
 *       the customer-facing productCode. item() resolves the "default
 *       class" row (class_category_id1 IS NULL AND class_category_id2 IS
 *       NULL — the same convention SqlFavoriteStorage / SqlCartCommand
 *       use), INNER JOINs dtb_product for the header name and LEFT JOINs
 *       the empty mtb_sale_type master for saleTypeName. No generator:
 *       it is read-only, the productCode is never minted by this slice.
 *       A productCode that only appears on a non-default variation row
 *       is an honest miss → null)
 *   - CsvColumnConfigStorageInterface → SqlCsvColumnConfigStorage
 *       (Phase 2b — CSV column-config storage against dtb_csv. Each row
 *       is one column-config entry; a csvType owns many rows.
 *       listByType reads the per-type vector sorted by sort_no;
 *       replaceType is an atomic per-type vector replace (DELETE all
 *       rows for the csvType, INSERT the new vector, in a savepoint-
 *       aware transaction — same shape as SqlCartCommand). csv_type_id
 *       is an enforced FK to the empty mtb_csv_type master — seeded via
 *       seedCsvTypes, same precedent as seedSaleTypes. No generator: the
 *       row identity is the AUTO_INCREMENT id, never minted by the slice)
 *   - PluginStorageInterface       → SqlPluginStorage (Phase 2b — plugin
 *       lifecycle registry against dtb_plugin. listAll / findByCode /
 *       install / uninstall / setEnabled, all keyed by the natural key
 *       `code` (the column is `code`, NOT `plugin_code`). install is
 *       idempotent — probe code, INSERT only if absent, UPDATE
 *       initialized only on a registered-but-not-installed row;
 *       uninstall is a scoped DELETE; setEnabled is a guarded enabled
 *       flip. The BeMart `installed` axis maps onto `dtb_plugin.initialized`.
 *       dtb_plugin has no FK constraints, so no master seeding — but the
 *       table is empty in the structure-only dump, so the hypermedia
 *       test seeds the two demo plugins via seedPlugins. No generator:
 *       the row identity is the AUTO_INCREMENT id, never minted)
 *   - PasswordResetTokenStorageInterface → SqlPasswordResetTokenStorage
 *       (Phase 2b — password-reset token issue / lookup / consume.
 *       EC-CUBE 4.3 has NO separate token table — the token lives as
 *       the `reset_key` / `reset_expire` columns on dtb_customer, so
 *       put / getByResetKey / delete are column UPDATEs / a SELECT on
 *       dtb_customer (Option A: mirror EC-CUBE, no schema change). put
 *       is a column UPDATE keyed by customerId so a re-issue replaces
 *       the prior token (latest-wins); delete nulls both columns
 *       (single-use). getByResetKey returns the row REGARDLESS of
 *       expiry — the consumer PasswordResetCompleted does its own
 *       `expiresAt < now` check, matching the Fake. No generator: the
 *       reset_key is minted by the issuer's CustomerIdGenerator, not by
 *       this storage. The surface is disjoint from SqlCustomerQuery /
 *       SqlCustomerCommand which never touch the reset_* columns)
 *   - AdminQueryInterface          → SqlAdminQuery   (Admin auth Phase B)
 *   - AdminCommandInterface        → SqlAdminCommand (Admin auth Phase B —
 *       full CRUD against dtb_member; soft-delete flips work_id to 0
 *       rather than DELETE so login_history FK survives and the admin
 *       grid can re-activate)
 *   - AdminIdGeneratorInterface    → SqlAdminIdGenerator (Admin auth
 *       Phase B — MemberCreating needs a numeric id pre-allocated so
 *       SqlAdminCommand::create can persist with an explicit PK; the
 *       Fake generator emits a 32-char `ad…` hex that the SQL impl
 *       rejects as non-numeric, same shape as the Block / Page / Tag /
 *       News / TaxRule generator pairings)
 *   - PDO::class                   → shared test PDO singleton
 *
 * NOT rebound:
 *   - SessionInterface / AdminSessionInterface — admin/customer session
 *     is in-memory by design (the production cookie/JWT adapter is deferred).
 *     Subclasses rebind these via the same Module pattern when an
 *     authenticated actor is needed (FakeAdminSession / FakeSession).
 *
 * Why a sibling of AbstractSqlTestCase rather than a subclass?
 *   AbstractSqlTestCase is namespaced under `Be\Tests\Sql\` (it lives in
 *   the storage-layer test suite where there is no AppModule). The
 *   Resource hypermedia tests need to import AppModule which sits in
 *   the application namespace `MyVendor\BeMart\Module\`. Sharing fixture
 *   helpers via {@see SqlFixturesTrait} keeps DRY without crossing the
 *   two-layer namespace boundary.
 */
abstract class AbstractResourceSqlTestCase extends TestCase
{
    use SqlFixturesTrait;

    protected ResourceInterface $resource;
    protected PDO $pdo;

    protected function setUp(): void
    {
        // Reuse the same lazy bootstrap as AbstractSqlTestCase — first
        // call drops + recreates `eccubedb_test` and stashes a PDO in
        // $GLOBALS, every subsequent call reuses it.
        if (! isset($GLOBALS['BEMART_SQL_BOOTSTRAP'])) {
            require dirname(__DIR__, 3) . '/be/tests/Sql/bootstrap.php';
        }

        /** @var array{skip: bool, reason?: string, pdo?: PDO}|null $bootstrap */
        $bootstrap = $GLOBALS['BEMART_SQL_BOOTSTRAP'] ?? null;
        if ($bootstrap === null) {
            throw new RuntimeException(
                'SQL bootstrap failed to populate $GLOBALS[\'BEMART_SQL_BOOTSTRAP\']',
            );
        }

        if ($bootstrap['skip']) {
            $this->markTestSkipped($bootstrap['reason'] ?? 'SQL suite disabled');
        }

        $this->pdo = $bootstrap['pdo'];
        $this->pdo->beginTransaction();

        $this->resource = $this->buildResource();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Build a Resource client whose AppModule has the SQL bindings
     * stitched on top. Subclasses can override `extraOverride()` to
     * layer additional bindings (e.g. FakeAdminSession with a fixed
     * adminId) without re-stating the SQL plumbing on every call site.
     */
    protected function buildResource(): ResourceInterface
    {
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override($this->sqlOverrideModule());

        $extra = $this->extraOverride();
        if ($extra !== null) {
            $base->override($extra);
        }

        $injector = new Injector($base, dirname(__DIR__, 3) . '/var/tmp/test');

        return $injector->getInstance(ResourceInterface::class);
    }

    /**
     * Hook for subclasses to layer their own bindings on top of the
     * SQL override (typically rebinding SessionInterface or
     * AdminSessionInterface to a FakeSession / FakeAdminSession with a
     * fixed actor). Return null to skip.
     */
    protected function extraOverride(): AbstractModule|null
    {
        return null;
    }

    /**
     * The SQL override module: binds `PDO::class` to the shared test
     * connection and rebinds every interface for which we have a
     * `Sql*` implementation.
     *
     * Singleton-scoped on the PDO instance — the same connection must
     * be reused by every Sql class so the test transaction wraps every
     * read and write.
     */
    private function sqlOverrideModule(): AbstractModule
    {
        $pdo = $this->pdo;

        return new class ($pdo) extends AbstractModule {
            public function __construct(private readonly PDO $pdo)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                // PDO is the shared test connection — bound by instance
                // so every Sql class resolves the same handle (and thus
                // the same per-test transaction).
                $this->bind(PDO::class)->toInstance($this->pdo);

                // Phase 2a Sql impls. Linked bindings; Ray.Di will
                // construct each Sql class on first request, injecting
                // the PDO above via the constructor.
                $this->bind(CustomerQueryInterface::class)
                    ->to(SqlCustomerQuery::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CustomerCommandInterface::class)
                    ->to(SqlCustomerCommand::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CustomerIdGeneratorInterface::class)
                    ->to(SqlCustomerIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(EmailUniquenessCheckerInterface::class)
                    ->to(SqlEmailUniquenessChecker::class)
                    ->in(Scope::SINGLETON);
                $this->bind(OrderQueryInterface::class)
                    ->to(SqlOrderQuery::class)
                    ->in(Scope::SINGLETON);
                $this->bind(OrderCommandInterface::class)
                    ->to(SqlOrderCommand::class)
                    ->in(Scope::SINGLETON);
                $this->bind(FavoriteStorageInterface::class)
                    ->to(SqlFavoriteStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CartQueryInterface::class)
                    ->to(SqlCartQuery::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CartCommandInterface::class)
                    ->to(SqlCartCommand::class)
                    ->in(Scope::SINGLETON);
                $this->bind(AddressStorageInterface::class)
                    ->to(SqlAddressStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(AddressIdGeneratorInterface::class)
                    ->to(SqlAddressIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(TagStorageInterface::class)
                    ->to(SqlTagStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(TagIdGeneratorInterface::class)
                    ->to(SqlTagIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(BaseInfoStorageInterface::class)
                    ->to(SqlBaseInfoStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(TaxRuleStorageInterface::class)
                    ->to(SqlTaxRuleStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(TaxRuleIdGeneratorInterface::class)
                    ->to(SqlTaxRuleIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(NewsStorageInterface::class)
                    ->to(SqlNewsStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(NewsIdGeneratorInterface::class)
                    ->to(SqlNewsIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(PageStorageInterface::class)
                    ->to(SqlPageStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(PageIdGeneratorInterface::class)
                    ->to(SqlPageIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(BlockStorageInterface::class)
                    ->to(SqlBlockStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(BlockIdGeneratorInterface::class)
                    ->to(SqlBlockIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CategoryStorageInterface::class)
                    ->to(SqlCategoryStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CategoryIdGeneratorInterface::class)
                    ->to(SqlCategoryIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(ClassNameStorageInterface::class)
                    ->to(SqlClassNameStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(ClassNameIdGeneratorInterface::class)
                    ->to(SqlClassNameIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(ClassCategoryStorageInterface::class)
                    ->to(SqlClassCategoryStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(ClassCategoryIdGeneratorInterface::class)
                    ->to(SqlClassCategoryIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(AdminQueryInterface::class)
                    ->to(SqlAdminQuery::class)
                    ->in(Scope::SINGLETON);
                $this->bind(AdminCommandInterface::class)
                    ->to(SqlAdminCommand::class)
                    ->in(Scope::SINGLETON);
                $this->bind(AdminIdGeneratorInterface::class)
                    ->to(SqlAdminIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(LayoutStorageInterface::class)
                    ->to(SqlLayoutStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(TemplateStorageInterface::class)
                    ->to(SqlTemplateStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(LoginHistoryStorageInterface::class)
                    ->to(SqlLoginHistoryStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(PaymentMethodAdminStorageInterface::class)
                    ->to(SqlPaymentMethodAdminStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(PaymentMethodAdminIdGeneratorInterface::class)
                    ->to(SqlPaymentMethodAdminIdGenerator::class)
                    ->in(Scope::SINGLETON);
                $this->bind(TradeLawStorageInterface::class)
                    ->to(SqlTradeLawStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(ShippingAddressStorageInterface::class)
                    ->to(SqlShippingAddressStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(ProductClassQueryInterface::class)
                    ->to(SqlProductClassQuery::class)
                    ->in(Scope::SINGLETON);
                $this->bind(CsvColumnConfigStorageInterface::class)
                    ->to(SqlCsvColumnConfigStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(PluginStorageInterface::class)
                    ->to(SqlPluginStorage::class)
                    ->in(Scope::SINGLETON);
                $this->bind(PasswordResetTokenStorageInterface::class)
                    ->to(SqlPasswordResetTokenStorage::class)
                    ->in(Scope::SINGLETON);
            }
        };
    }
}
