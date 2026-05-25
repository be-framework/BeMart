<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Final\MypageHistoryFetched;
use MyVendor\BeMart\Be\Input\GetMypageHistoryInput;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Pilot (goMypageHistory) — Direct safe-read with AUTHN + AUTHZ.
 *
 * Reuses the SEED_ORDER_NO past order seeded by FakeFinalizedOrderStorage
 * (owned by `customer-001`). Session is rebound per-case to drive the
 * happy / 401 / 403 branches.
 */
final class MypageHistoryFetchedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindSession('customer-001');
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsOrderHeaderAndItems(): void
    {
        $final = ($this->becoming)(new GetMypageHistoryInput(
            orderNo: FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ));

        $this->assertInstanceOf(MypageHistoryFetched::class, $final);
        $this->assertSame(FakeFinalizedOrderStorage::SEED_ORDER_NO, $final->orderNo);
        $this->assertSame('customer-001', $final->customerId);
        $this->assertSame(12700, $final->total);
        $this->assertSame(12700, $final->paymentTotal);
        $this->assertSame(127, $final->addPoint);
        $this->assertSame(0, $final->usePoint);
        $this->assertSame('2026-04-01 10:00:00', $final->orderDate);

        // Phase 3 enrichment — line items are carried per shipping block.
        $this->assertCount(1, $final->shippings);
        $codes = [];
        foreach ($final->shippings[0]['items'] as $item) {
            $codes[$item['productCode']] = $item;
        }

        $this->assertArrayHasKey('sample-001', $codes);
        $this->assertSame('サンプル商品 A', $codes['sample-001']['productName']);
        $this->assertSame(1, $codes['sample-001']['quantity']);
        $this->assertSame(1200, $codes['sample-001']['unitPrice']);

        $this->assertArrayHasKey('sample-002', $codes);
        $this->assertSame(9800, $codes['sample-002']['unitPrice']);
    }

    public function testHappyPathComposesEnrichedHistoryProjection(): void
    {
        $final = ($this->becoming)(new GetMypageHistoryInput(
            orderNo: FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ));

        $this->assertInstanceOf(MypageHistoryFetched::class, $final);

        // Order message + payment method.
        $this->assertSame('配送は平日希望です。', $final->message);
        $this->assertSame('銀行振込', $final->paymentMethod);

        // Per-shipping address block.
        $shipping = $final->shippings[0];
        $this->assertSame('山田', $shipping['name01']);
        $this->assertSame('太郎', $shipping['name02']);
        $this->assertSame('ヤマダ', $shipping['kana01']);
        $this->assertSame('530-0001', $shipping['postalCode']);
        $this->assertSame('大阪府', $shipping['prefName']);
        $this->assertSame('大阪市北区梅田', $shipping['addr01']);
        $this->assertSame('サンプル宅配便', $shipping['deliveryName']);
        $this->assertSame('2026-04-03', $shipping['deliveryDate']);
        $this->assertSame('午前中', $shipping['deliveryTime']);

        // Mail-delivery history.
        $this->assertCount(1, $final->mailHistories);
        $this->assertSame('2026-04-01 10:05:00', $final->mailHistories[0]['sendDate']);
        $this->assertSame('ご注文ありがとうございます', $final->mailHistories[0]['mailSubject']);
        $this->assertStringContainsString('ありがとうございます', $final->mailHistories[0]['mailBody']);
    }

    public function testUnknownOrderRaisesOrderNotFound(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new GetMypageHistoryInput(
            orderNo: 'never00000000000000000000000000z',
        ));
    }

    public function testWrongOwnerRaisesUnauthorized(): void
    {
        $this->rebindSession('customer-999');

        $this->expectException(UnauthorizedOrderAccessException::class);
        ($this->becoming)(new GetMypageHistoryInput(
            orderNo: FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ));
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->rebindSession(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetMypageHistoryInput(
            orderNo: FakeFinalizedOrderStorage::SEED_ORDER_NO,
        ));
    }
}
