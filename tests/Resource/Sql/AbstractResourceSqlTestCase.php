<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\SqlAddressStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlBaseInfoStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlCartCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlCartQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlCustomerQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlFavoriteStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlNewsStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;
use MyVendor\BeMart\Be\Reason\Query\SqlTagStorage;
use MyVendor\BeMart\Be\Reason\Query\SqlTaxRuleStorage;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AddressIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\NewsIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\SqlAddressIdGenerator;
use MyVendor\BeMart\Be\Reason\Service\SqlNewsIdGenerator;
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
            }
        };
    }
}
