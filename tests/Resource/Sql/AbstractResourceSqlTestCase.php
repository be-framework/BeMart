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
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\LoginHistoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
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
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlLayoutStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlLoginHistoryStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlNewsStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlPageStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlPaymentMethodAdminStorage;
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
use MyVendor\BeMart\Be\Reason\Service\NewsIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PageIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodAdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\SqlAddressIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlAdminIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlBlockIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlClassCategoryIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlClassNameIdGenerator;
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
 *   - OrderQueryInterface          → SqlOrderQuery
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
 *   - CustomerCommandInterface  — no SqlCustomerCommand impl yet (Phase 2b)
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
                $this->bind(OrderQueryInterface::class)
                    ->to(SqlOrderQuery::class)
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
            }
        };
    }
}
