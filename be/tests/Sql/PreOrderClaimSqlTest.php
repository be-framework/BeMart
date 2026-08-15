<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use Aura\Sql\DecoratedPdo;
use BEAR\AppMeta\Meta;
use MyVendor\BeMart\Be\Exception\PreOrderAlreadyClaimedException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Service\PreOrderClaimInterface;
use MyVendor\BeMart\Be\Reason\Service\SqlPreOrderClaim;
use MyVendor\BeMart\Module\MediaQueryRuntimeModule;
use MyVendor\BeMart\Module\TestModule;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use RuntimeException;

use function assert;
use function dirname;

/**
 * MySQL contract of the checkout claim — the arbiter that decides which
 * request may complete a pre-order.
 *
 * Only the real database can answer this: the whole mechanism is a
 * conditional `UPDATE … WHERE order_status_id = 8` whose row lock makes
 * the two concurrent writers serial. The Fake store models the verdict
 * but not the arbitration.
 */
final class PreOrderClaimSqlTest extends TestCase
{
    private const PRE_ORDER_ID = 'claim0000000000000000000000000001';

    private PDO $pdo;
    private PreOrderClaimInterface $claim;

    protected function setUp(): void
    {
        if (! isset($GLOBALS['BEMART_SQL_BOOTSTRAP'])) {
            require __DIR__ . '/bootstrap.php';
        }

        /** @var array{skip: bool, reason?: string, pdo?: PDO}|null $bootstrap */
        $bootstrap = $GLOBALS['BEMART_SQL_BOOTSTRAP'] ?? null;
        if ($bootstrap === null) {
            throw new RuntimeException('SQL bootstrap did not publish $GLOBALS[\'BEMART_SQL_BOOTSTRAP\']');
        }

        if ($bootstrap['skip']) {
            $this->markTestSkipped($bootstrap['reason'] ?? 'SQL suite disabled');
        }

        $this->pdo = $bootstrap['pdo'];
        $this->pdo->beginTransaction();

        $module = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $module->override(new class ($this->pdo) extends AbstractModule {
            public function __construct(private readonly PDO $pdo)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->install(new MediaQueryRuntimeModule(new DecoratedPdo($this->pdo)));
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/test');

        // The Fake claim is bound for the `test` context; this suite is about
        // the SQL adapter, so ask for it by class.
        $claim = $injector->getInstance(SqlPreOrderClaim::class);
        assert($claim instanceof PreOrderClaimInterface);
        $this->claim = $claim;

        $this->insertProcessingPreOrder();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testFirstClaimWinsAndTakesTheRowOutOfProcessing(): void
    {
        $claimed = $this->claim->claim(self::PRE_ORDER_ID, 'orderno-first');

        $this->assertSame('orderno-first', $claimed->orderNo);
        $this->assertSame(
            [FinalizedOrderEntity::STATUS_NEW, 'orderno-first'],
            $this->rowState(),
        );
    }

    public function testSecondClaimLosesAndReportsTheWinner(): void
    {
        $this->claim->claim(self::PRE_ORDER_ID, 'orderno-first');

        $second = $this->claim->claim(self::PRE_ORDER_ID, 'orderno-second');

        $this->assertSame('orderno-first', $second->orderNo);
        $this->expectException(PreOrderAlreadyClaimedException::class);
        $second->assertHeldBy('orderno-second');
    }

    public function testLosingClaimLeavesTheWinnersRowUntouched(): void
    {
        $this->claim->claim(self::PRE_ORDER_ID, 'orderno-first');
        $this->claim->claim(self::PRE_ORDER_ID, 'orderno-second');

        $this->assertSame(
            [FinalizedOrderEntity::STATUS_NEW, 'orderno-first'],
            $this->rowState(),
        );
    }

    public function testClaimOnAnUnknownPreOrderReportsNoHolder(): void
    {
        $claimed = $this->claim->claim('claim0000000000000000000000000099', 'orderno-ghost');

        $this->assertSame('', $claimed->orderNo);
        $this->expectException(PreOrderAlreadyClaimedException::class);
        $claimed->assertHeldBy('orderno-ghost');
    }

    /** @return array{0: int, 1: string} */
    private function rowState(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT order_status_id, order_no FROM dtb_order WHERE pre_order_id = :preOrderId',
        );
        $statement->execute(['preOrderId' => self::PRE_ORDER_ID]);
        /** @var array{order_status_id: string, order_no: string|null} $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return [(int) $row['order_status_id'], (string) $row['order_no']];
    }

    private function insertProcessingPreOrder(): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO dtb_order
                (pre_order_id, order_no, order_status_id, name01, name02, subtotal, discount,
                 delivery_fee_total, charge, tax, total, payment_total, add_point, use_point,
                 create_date, update_date, discriminator_type)
             VALUES
                (:preOrderId, NULL, :status, \'claim\', \'test\', 0, 0, 0, 0, 0, 0, 0, 0, 0,
                 NOW(), NOW(), \'order\')',
        );
        $statement->execute([
            'preOrderId' => self::PRE_ORDER_ID,
            'status' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
    }
}
