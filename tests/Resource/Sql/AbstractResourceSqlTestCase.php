<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use Aura\Sql\DecoratedPdo;
use BEAR\AppMeta\Meta;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Tests\Sql\SqlFixturesTrait;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Module\MediaQueryRuntimeModule;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
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
 * Critical contract — Ray.MediaQuery connection binding:
 *   AppModule does not bind a SQL connection (test default is Fake-based).
 *   The override module below installs MediaQueryRuntimeModule with the
 *   SHARED test PDO singleton, so every SQL-backed public interface resolves
 *   as a Ray.MediaQuery direct proxy over the SAME connection — the test
 *   fixtures' INSERTs and the production Becoming chain's SELECTs operate
 *   on the same transaction that tearDown rolls back.
 *
 * Rebound interfaces (production-Fake → test-Sql):
 *   - CustomerQueryInterface       → CustomerQueryInterface
 *   - CustomerCommandInterface     → CustomerCommandInterface (Phase 2b —
 *       write side of dtb_customer: register / activate / update /
 *       updatePassword. Mirrors CustomerQueryInterface's column↔field map
 *       so a read-after-write round-trips. secret_key is NOT NULL
 *       UNIQUE — register synthesises a unique token when the entity
 *       carries a null; activate keeps the key rather than nulling it)
 *   - CustomerIdQueryInterface → direct MediaQuery customer id proxy (Phase 2b —
 *       CustomerRegistering needs a numeric id pre-allocated so
 *       CustomerCommandInterface::register can persist with an explicit PK;
 *       the FakeQuery fixture emits 32-char hex that the SQL impl rejects
 *       as non-numeric, same shape as the Admin id-query pairing)
 *   - EmailUniquenessQueryInterface → EmailUniquenessQueryInterface
 *       (Phase 2b — registration / profile-update duplicate-email
 *       guard, a trivial existence probe against dtb_customer.email,
 *       the natural read-guard companion of the customer write side)
 *   - OrderQueryInterface          → OrderQueryInterface
 *   - OrderCommandInterface        → OrderCommandInterface (Phase 2b — write
 *       side of dtb_order: register / update / updateStatus. register
 *       is an UPSERT keyed by pre_order_id — it PROMOTES an existing
 *       pre-order row (PROCESSING→NEW, EC-CUBE's mutate-the-same-row
 *       checkout semantics) and only INSERTs when no pre-order exists
 *       (admin data-entry order). Mirrors OrderQueryInterface's column↔field
 *       map so a read-after-write round-trips. order_status_id has no
 *       FK constraint so no master seeding is needed; customer_id is an
 *       int FK — a non-numeric BeMart handle writes NULL)
 *   - FavoriteStorageInterface     → FavoriteStorageInterface
 *   - CartQueryInterface           → CartQueryInterface
 *   - CartCommandInterface         → CartCommandInterface
 *   - AddressStorageInterface      → AddressStorageInterface  (Phase 2b)
 *   - AddressIdQueryInterface  → direct MediaQuery address id proxy (Phase 2b —
 *       CustomerAddressCreated needs a numeric id pre-allocated so
 *       AddressStorageInterface can persist with that explicit PK)
 *   - TagStorageInterface          → TagStorageInterface  (Phase 2b)
 *   - TagIdQueryInterface      → direct MediaQuery tag id proxy (Phase 2b —
 *       TagCreated needs a numeric id pre-allocated so TagStorageInterface
 *       can persist with that explicit PK; the FakeQuery fixture emits
 *       a `tg-` prefix that the SQL impl rejects as non-numeric)
 *   - BaseInfoStorageInterface     → BaseInfoStorageInterface (Phase 2b —
 *       singleton row at id=1; the SQL impl returns installer-default
 *       fields when the row is missing so the hypermedia contract is
 *       identical to the Fake-backed baseline with no extra fixture
 *       setup required)
 *   - TaxRuleStorageInterface      → TaxRuleStorageInterface  (Phase 2b)
 *   - TaxRuleIdQueryInterface  → direct MediaQuery tax-rule id proxy (Phase 2b —
 *       TaxRuleCreated needs a numeric id pre-allocated so
 *       TaxRuleStorageInterface can persist with that explicit PK; the
 *       FakeQuery fixture emits hex that the SQL impl rejects as
 *       non-numeric, same shape as the Tag id-query pairing)
 *   - NewsStorageInterface         → NewsStorageInterface  (Phase 2b)
 *   - NewsIdQueryInterface     → direct MediaQuery news id proxy (Phase 2b —
 *       NewsCreated needs a numeric id pre-allocated so NewsStorageInterface
 *       can persist with that explicit PK; the FakeQuery fixture emits an
 *       `nw-` prefix that the SQL impl rejects as non-numeric, same
 *       shape as the Tag / TaxRule id-query pairings)
 *   - PageStorageInterface         → PageStorageInterface  (Phase 2b)
 *   - PageIdQueryInterface     → direct MediaQuery page id proxy (Phase 2b —
 *       PageCreated needs a numeric id pre-allocated so PageStorageInterface
 *       can persist with that explicit PK; the FakeQuery fixture emits a
 *       `pg-` prefix that the SQL impl rejects as non-numeric, same
 *       shape as the Tag / News / TaxRule id-query pairings)
 *   - BlockStorageInterface        → BlockStorageInterface  (Phase 2b)
 *   - BlockIdQueryInterface    → direct MediaQuery block id proxy (Phase 2b —
 *       BlockCreated needs a numeric id pre-allocated so BlockStorageInterface
 *       can persist with that explicit PK; the FakeQuery fixture emits a
 *       `bk-` prefix that the SQL impl rejects as non-numeric, same
 *       shape as the Page / Tag / News / TaxRule id-query pairings)
 *   - CategoryStorageInterface     → CategoryStorageInterface  (Phase 2b)
 *   - CategoryIdQueryInterface → direct MediaQuery category id proxy (Phase 2b —
 *       CategoryCreated needs a numeric id pre-allocated so
 *       CategoryStorageInterface can persist with that explicit PK; the Fake
 *       fixture emits 32-char hex that the SQL impl rejects as
 *       non-numeric, same shape as the Block / Page / Tag / News /
 *       TaxRule id-query pairings)
 *   - ClassNameStorageInterface    → ClassNameStorageInterface  (Phase 2b —
 *       product-variation AXIS, dtb_class_name; remove pre-clears child
 *       dtb_class_category rows to avoid FK 1451, same shape as the
 *       Category → dtb_product_category cascade)
 *   - ClassNameIdQueryInterface → direct MediaQuery class-name id proxy (Phase 2b —
 *       the ClassName-create Final needs a numeric id pre-allocated so
 *       ClassNameStorageInterface can persist with that explicit PK; the Fake
 *       fixture emits 32-char hex that the SQL impl rejects as
 *       non-numeric, same shape as the Category id-query pairing)
 *   - ClassCategoryStorageInterface → ClassCategoryStorageInterface (Phase 2b —
 *       product-variation VALUE under a ClassName axis, dtb_class_category;
 *       every row is pinned to a parent dtb_class_name via the
 *       class_name_id FK. remove issues a plain DELETE — unlike
 *       ClassNameStorageInterface it does NOT pre-clear children: deleting a
 *       single variant value must not cascade-delete the dtb_product_class
 *       rows that use it)
 *   - ClassCategoryIdQueryInterface → direct MediaQuery class-category id proxy
 *       (Phase 2b — the ClassCategory-create Final needs a numeric id
 *       pre-allocated so ClassCategoryStorageInterface can persist with that
 *       explicit PK; the FakeQuery fixture emits 32-char hex that the SQL
 *       impl rejects as non-numeric, same shape as the ClassName /
 *       Category id-query pairings)
 *   - LayoutStorageInterface       → LayoutStorageInterface (Phase 2b — admin
 *       CMS layout list + update against dtb_layout. The interface has
 *       only list / getById / put — no create, no delete affordance per
 *       ALPS — so there is NO LayoutIdProvider pairing: layoutIds are
 *       never minted by the BeMart slice, only read back from rows the
 *       installer/fixture seeded. LayoutStorageInterface rejects a non-numeric
 *       id as a miss, so `nonexistent` folds to a 404 on both backends)
 *   - TemplateStorageInterface     → TemplateStorageInterface (Phase 2b — admin
 *       design-template registry list against dtb_template. The
 *       interface is `list()` only — ALPS exposes a single affordance
 *       (`goTemplateList`), no create / update / delete, no upload flow
 *       — so there is NO TemplateIdProvider and no getById / put /
 *       remove. Templates are filesystem-backed in EC-CUBE; dtb_template
 *       is only the installed-flavour registry, read-only from this
 *       slice. Same column shape as dtb_layout)
 *   - LoginHistoryStorageInterface → LoginHistoryStorageInterface (Phase 2b —
 *       admin login-attempt audit log against dtb_login_history. The
 *       interface is listRecent + append — an append + list audit log,
 *       no getById / update / delete (an audit row has no
 *       client-meaningful handle and is never mutated), so there is NO
 *       LoginHistoryIdProvider. login_history_status_id is a NOT NULL
 *       FK to the empty mtb_login_history_status master — seeded via
 *       seedLoginHistoryStatus, same precedent as seedAdminMasters)
 *   - PaymentMethodAdminStorageInterface → PaymentMethodAdminStorageInterface
 *       (Phase 2b — admin payment-method master CRUD against
 *       dtb_payment. list / getById / put / remove; remove pre-clears
 *       child dtb_payment_option link rows to avoid FK 1451, same shape
 *       as the Block → dtb_block_position cascade)
 *   - PaymentMethodAdminIdQueryInterface → direct MediaQuery payment id proxy
 *       (Phase 2b — PaymentMethodAdminCreated needs a numeric id
 *       pre-allocated so PaymentMethodAdminStorageInterface can persist with
 *       that explicit PK; the FakeQuery fixture emits 32-char hex that the
 *       SQL impl rejects as non-numeric, same shape as the Block / Tag /
 *       News id-query pairings)
 *   - TradeLawStorageInterface     → TradeLawStorageInterface (Phase 2b — the
 *       特定商取引法 page. EC-CUBE models it as up to 15 per-item rows;
 *       the Wave-8 interface is single-blob — get() returns the whole
 *       page body, update() replaces it — so TradeLawStorageInterface stores
 *       the blob in ONE carrier row's description column at
 *       dtb_tradelaw.id=1, the same singleton-row shape BaseInfoStorageInterface
 *       uses for dtb_base_info.id=1. No id provider: the row identity is
 *       fixed, never minted. get() falls back to TradeLawStorageInterface's
 *       installer-default body when the carrier row is absent so the
 *       hypermedia contract is identical to the Fake-backed baseline
 *       with no extra fixture setup required)
 *   - ShippingAddressStorageInterface → ShippingAddressStorageInterface
 *       (Phase 2b — per-order delivery target against dtb_shipping.
 *       getByOrderNo / put / listAll, all keyed by the customer-facing
 *       orderNo. dtb_shipping references the order via the int FK
 *       order_id, so every method resolves order_no → dtb_order.id (a
 *       sub-SELECT for reads, a JOIN for listAll). put enforces the
 *       single-row-per-order invariant by probing order_id and
 *       UPDATEing in place — dtb_shipping has no UNIQUE on order_id.
 *       No id provider: the row identity is the order_id, never minted.
 *       pref_id is a nullable FK to the empty mtb_pref master — pref=0
 *       writes NULL, NULL reads back as 0)
 *   - ProductClassQueryInterface   → ProductClassQueryInterface (Phase 2b —
 *       the per-variation SKU lookup against dtb_product_class, keyed by
 *       the customer-facing productCode. item() resolves the "default
 *       class" row (class_category_id1 IS NULL AND class_category_id2 IS
 *       NULL — the same convention FavoriteStorageInterface / CartCommandInterface
 *       use), INNER JOINs dtb_product for the header name and LEFT JOINs
 *       the empty mtb_sale_type master for saleTypeName. No id provider:
 *       it is read-only, the productCode is never minted by this slice.
 *       A productCode that only appears on a non-default variation row
 *       is an honest miss → null)
 *   - ProductQueryInterface        → ProductQueryInterface (Phase 2b — the
 *       admin product read side. item / listAll / search / listForExport
 *       against the flattened Product × default-ProductClass row:
 *       productName / productStatus / description / searchWord / note
 *       from dtb_product, productCode / price02 / stock from the default
 *       dtb_product_class row (both class_category_id* axes NULL — the
 *       same convention ProductClassQueryInterface uses). product_code lives
 *       on dtb_product_class, so the natural key resolves through the
 *       class table. No id provider: it is read-only)
 *   - ProductCommandInterface      → ProductCommandInterface (Phase 2b — the
 *       admin product write side. create / update / delete / copy /
 *       bulkUpdateStatus. ProductEntity is a flattened two-table row,
 *       so create / copy INSERT BOTH a dtb_product header and its
 *       default dtb_product_class row inside one atomic unit (SAVEPOINT-
 *       aware, same shape as CsvColumnConfigStorageInterface). delete is a
 *       SOFT delete — flips dtb_product.product_status_id to
 *       STATUS_WITHDRAWN=3, never a physical DELETE (order-history
 *       snapshots must survive), idempotent on replay. bulkUpdateStatus
 *       flips product_status_id for a list of codes, returning the
 *       count actually changed. product_status_id is a nullable FK to
 *       the empty mtb_product_status master — seeded via
 *       seedProductStatus. No id provider: ProductEntity is keyed by the
 *       caller-supplied productCode string; dtb_product.id is autoinc
 *       and internal)
 *   - CsvColumnConfigStorageInterface → CsvColumnConfigStorageInterface
 *       (Phase 2b — CSV column-config storage against dtb_csv. Each row
 *       is one column-config entry; a csvType owns many rows.
 *       listByType reads the per-type vector sorted by sort_no;
 *       replaceType is an atomic per-type vector replace (DELETE all
 *       rows for the csvType, INSERT the new vector, in a savepoint-
 *       aware transaction — same shape as CartCommandInterface). csv_type_id
 *       is an enforced FK to the empty mtb_csv_type master — seeded via
 *       seedCsvTypes, same precedent as seedSaleTypes. No id provider: the
 *       row identity is the AUTO_INCREMENT id, never minted by the slice)
 *   - PluginStorageInterface       → PluginStorageInterface (Phase 2b — plugin
 *       lifecycle registry against dtb_plugin. listAll / findByCode /
 *       install / uninstall / setEnabled, all keyed by the natural key
 *       `code` (the column is `code`, NOT `plugin_code`). install is
 *       idempotent — probe code, INSERT only if absent, UPDATE
 *       initialized only on a registered-but-not-installed row;
 *       uninstall is a scoped DELETE; setEnabled is a guarded enabled
 *       flip. The BeMart `installed` axis maps onto `dtb_plugin.initialized`.
 *       dtb_plugin has no FK constraints, so no master seeding — but the
 *       table is empty in the structure-only dump, so the hypermedia
 *       test seeds the two demo plugins via seedPlugins. No id provider:
 *       the row identity is the AUTO_INCREMENT id, never minted)
 *   - PasswordResetTokenStorageInterface → PasswordResetTokenStorageInterface
 *       (Phase 2b — password-reset token issue / lookup / consume.
 *       EC-CUBE 4.3 has NO separate token table — the token lives as
 *       the `reset_key` / `reset_expire` columns on dtb_customer, so
 *       put / getByResetKey / delete are column UPDATEs / a SELECT on
 *       dtb_customer (Option A: mirror EC-CUBE, no schema change). put
 *       is a column UPDATE keyed by customerId so a re-issue replaces
 *       the prior token (latest-wins); delete nulls both columns
 *       (single-use). getByResetKey returns the row REGARDLESS of
 *       expiry — the consumer PasswordResetCompleted does its own
 *       `expiresAt < now` check, matching the Fake. No id provider: the
 *       reset_key is minted by the issuer's CustomerIdProvider, not by
 *       this storage. The surface is disjoint from CustomerQueryInterface /
 *       CustomerCommandInterface which never touch the reset_* columns)
 *   - DeliveryStorageInterface     → DeliveryStorageInterface (Phase 2b — admin
 *       delivery-method master CRUD against dtb_delivery. list / getById
 *       / put / remove. After the 厳密移植 narrowing (Delivery Phase A)
 *       the 3-field DeliveryEntity (deliveryId / deliveryName / visible)
 *       is 1:1 with dtb_delivery's modeled columns — dtb_delivery has no
 *       fee columns, so DeliveryStorageInterface touches none. sale_type_id is
 *       a nullable FK to the empty mtb_sale_type master — written NULL
 *       since the slice projects no sale-type axis. remove is a plain
 *       DELETE: the BeMart slice never INSERTs child dtb_delivery_fee /
 *       dtb_delivery_time / dtb_payment_option rows so there is no
 *       cascade to pre-clear)
 *   - DeliveryIdQueryInterface → direct MediaQuery delivery id proxy (Phase 2b —
 *       DeliveryCreated needs a numeric id pre-allocated so
 *       DeliveryStorageInterface can persist with that explicit PK; the Fake
 *       fixture emits 32-char hex that the SQL impl rejects as
 *       non-numeric, same shape as the Block / Page / Tag id query
 *       pairings)
 *   - MailTemplateStorageInterface → MailTemplateStorageInterface (Phase 2b —
 *       admin mail-template list + per-id subject UPDATE against
 *       dtb_mail_template. list / findById / update. After the 厳密移植
 *       narrowing (MailTemplate Phase A) the 4-field MailTemplateEntity
 *       (mailTemplateId / mailTemplateName / fileName / subject) is 1:1
 *       with dtb_mail_template's modeled columns — the table has no body
 *       columns (EC-CUBE 4.3 stores mail bodies as on-disk Twig files),
 *       so MailTemplateStorageInterface touches none. update writes only
 *       mail_subject (+ update_date); file_name is fixed at create time.
 *       Update-only contract — no INSERT path, so there is no
 *       MailTemplateIdProvider pairing and update raises
 *       MailTemplateNotFoundException on an unknown id)
 *   - AdminQueryInterface          → AdminQueryInterface   (Admin auth Phase B)
 *   - AdminCommandInterface        → AdminCommandInterface (Admin auth Phase B —
 *       full CRUD against dtb_member; soft-delete flips work_id to 0
 *       rather than DELETE so login_history FK survives and the admin
 *       grid can re-activate)
 *   - AdminIdQueryInterface    → direct MediaQuery admin id proxy (Admin auth
 *       Phase B — MemberCreating needs a numeric id pre-allocated so
 *       AdminCommandInterface::create can persist with an explicit PK; the
 *       FakeQuery fixture emits a 32-char `ad…` hex that the SQL impl
 *       rejects as non-numeric, same shape as the Block / Page / Tag /
 *       News / TaxRule id-query pairings)
 *   - PDO::class                   → shared test PDO singleton
 *
 * NOT rebound:
 *   - CustomerSession / AdminSession — admin/customer session
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
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
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
     * SQL override (typically rebinding CustomerSession or
     * AdminSession to a FakeSession / FakeAdminSession with a
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
                $this->install(new MediaQueryRuntimeModule(new DecoratedPdo($this->pdo)));
            }
        };
    }
}
