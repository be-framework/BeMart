<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function str_contains;

/**
 * Phase 3 — セマンティック検証: Mypage 会員情報変更完了画面 (goMypageChangeComplete)。
 *
 * IdeaStore クリーンルーム移行後のテスト。EC-CUBE 実描画突合せは
 * @group ec-cube-parity-archived に退避済み。
 *
 * L1: 必須フィールド / データ出力。
 * L2: リンク href/rel — データ契約 (ChangeComplete::onGet の #[Link]) が規定する遷移先。
 */
final class MypageChangeCompleteHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L0 — HTTP / Content-Type
    // -------------------------------------------------------------------------

    public function testChangeCompleteReturns200WithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/mypage/change-complete');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // -------------------------------------------------------------------------
    // L1 — 必須フィールド / データ出力
    // -------------------------------------------------------------------------

    public function testChangeCompleteRendersAsHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testChangeCompleteOutputsPageTitle(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        $this->assertStringContainsString('IDEA STORE', $html, 'ページタイトルに IDEA STORE が含まれること');
        $this->assertStringContainsString('変更', $html, 'ページタイトルに変更の文脈が含まれること');
    }

    public function testChangeCompleteOutputsSuccessMessage(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        $this->assertStringContainsString('変更', $html, '完了メッセージに変更の語句が含まれること');
    }

    public function testChangeCompleteOutputsAccountNav(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        // マイページナビゲーションの各リンクが存在すること
        $this->assertStringContainsString('href="/mypage"', $html, '注文履歴へのリンク');
        $this->assertStringContainsString('href="/mypage/favorite-list"', $html, 'お気に入りへのリンク');
        $this->assertStringContainsString('href="/mypage/change"', $html, '会員情報変更へのリンク');
        $this->assertStringContainsString('href="/mypage/address-list"', $html, 'お届け先一覧へのリンク');
        $this->assertStringContainsString('href="/mypage/withdraw"', $html, '退会へのリンク');
    }

    // -------------------------------------------------------------------------
    // L2 — リンク href/rel (データ契約: ChangeComplete::onGet の #[Link])
    // -------------------------------------------------------------------------

    /** goMypage: #[Link(rel: 'goMypage', href: 'page://self/mypage')] */
    public function testChangeCompleteHasGoMypageLink(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        $this->assertTrue(
            str_contains($html, 'href="/mypage"'),
            'goMypage リンク (/mypage) が描画されていること',
        );
    }

    /** goTop: #[Link(rel: 'goTop', href: 'page://self/')] */
    public function testChangeCompleteHasGoTopLink(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        $this->assertTrue(
            str_contains($html, 'href="/"'),
            'goTop リンク (/) が描画されていること',
        );
    }

    /** フォームなし — submitTo は null。POST を誘導する <form> がないこと。 */
    public function testChangeCompleteHasNoSubmitForm(): void
    {
        $html = $this->resource->get('page://self/mypage/change-complete')->toString();

        $this->assertStringNotContainsString(
            'method="post"',
            $html,
            'submitTo=null なので POST フォームが存在しないこと',
        );
    }

    // -------------------------------------------------------------------------
    // Archived — EC-CUBE 実描画突合せ (IdeaStore 移行後は不要)
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testChangeCompleteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE 実描画突合せは IdeaStore クリーンルーム移行により廃止。'
            . ' セマンティック検証は L0/L1/L2 テストで継続。',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testChangeCompletePagePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE マークアップ構造チェックは IdeaStore クリーンルーム移行により廃止。'
            . ' DOM 独立の機能検証は L1/L2 テストで継続。',
        );
    }
}
