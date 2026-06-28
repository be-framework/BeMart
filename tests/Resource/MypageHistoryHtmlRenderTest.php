<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * 注文履歴詳細ページ (goMypageHistory) の HTML レンダリング検証。
 *
 * IdeaStore クリーンルーム再構築後の機能・セマンティック検証。
 * EC-CUBE 実描画との突合せ系テストは @group ec-cube-parity-archived に退避済み。
 *
 * L1 — 必須フィールドの出力確認（orderNo, orderDate, subtotal, total,
 *       paymentMethod, shipping 住所/明細, mailHistories など）
 * L2 — フォームの action/method と遷移リンクの href/rel 確認
 *       (doReorder: POST /mypage/reorder, goMypage: /mypage)
 */
final class MypageHistoryHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeSession('customer-001');
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function fetchHtml(): string
    {
        return $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'past0000000000000000000000000001',
        ])->toString();
    }

    // -------------------------------------------------------------------------
    // 基本構造
    // -------------------------------------------------------------------------

    public function testRendersValidHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/history', [
            'orderNo' => 'past0000000000000000000000000001',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testUsesIdeaStoreBaseLayout(): void
    {
        $html = $this->fetchHtml();
        // IdeaStore ベースレイアウトが適用されている
        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('idea-store.css', $html);
    }

    // -------------------------------------------------------------------------
    // L1 — 必須フィールドの出力
    // -------------------------------------------------------------------------

    public function testOutputsOrderNumber(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('past0000000000000000000000000001', $html);
    }

    public function testOutputsOrderDate(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('2026-04-01 10:00:00', $html);
    }

    public function testOutputsPaymentMethod(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('銀行振込', $html);
    }

    public function testOutputsSubtotal(): void
    {
        $html = $this->fetchHtml();
        // subtotal = 11,000
        $this->assertStringContainsString('11,000', $html);
    }

    public function testOutputsTotal(): void
    {
        $html = $this->fetchHtml();
        // total = 12,700
        $this->assertStringContainsString('12,700', $html);
    }

    public function testOutputsShippingRecipientName(): void
    {
        $html = $this->fetchHtml();
        // Fake seed の配送先氏名
        $this->assertStringContainsString('山田', $html);
        $this->assertStringContainsString('太郎', $html);
    }

    public function testOutputsShippingPostalCode(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('530', $html);
    }

    public function testOutputsProductName(): void
    {
        $html = $this->fetchHtml();
        // Fake seed の商品名
        $this->assertStringContainsString('サンプル商品 A', $html);
    }

    public function testOutputsProductCode(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('sample-', $html);
    }

    public function testOutputsPointInfo(): void
    {
        $html = $this->fetchHtml();
        // addPoint = 127
        $this->assertStringContainsString('127', $html);
    }

    public function testOutputsMailHistorySubject(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('ご注文ありがとうございます', $html);
    }

    public function testOutputsMailHistorySendDate(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('2026-04-01 10:05:00', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — フォーム action/method・リンク href
    // -------------------------------------------------------------------------

    /**
     * doReorder: POST /mypage/reorder、csrfToken + orderNo を送信。
     * #[Link(rel:'doReorder', href:'page://self/mypage/reorder', method:'post')]
     */
    public function testReorderFormHasCorrectActionAndMethod(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('action="/mypage/reorder"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testReorderFormContainsCsrfTokenField(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    public function testReorderFormContainsOrderNoField(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('name="orderNo"', $html);
        $this->assertStringContainsString('past0000000000000000000000000001', $html);
    }

    /**
     * goMypage: GET /mypage
     * #[Link(rel:'goMypage', href:'page://self/mypage')]
     */
    public function testBackLinkPointsToMypage(): void
    {
        $html = $this->fetchHtml();
        $this->assertStringContainsString('href="/mypage"', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE 実描画突合せ系テスト（退避）
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testHistoryPreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE マークアップ一致アサート。IdeaStore クリーンルーム移行により廃止。'
            . ' @group ec-cube-parity-archived に退避。'
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testHistoryHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE 実描画突合せ系テスト。IdeaStore クリーンルーム移行により廃止。'
            . ' @group ec-cube-parity-archived に退避。'
        );
    }
}
