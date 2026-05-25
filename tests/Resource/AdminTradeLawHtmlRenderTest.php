<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\HtmlTestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 — HTML render check for the admin 特定商取引法設定 Setting/Shop Tier-2 page.
 *
 * Render-smoke standard: the page renders a full admin-frame HTML
 * document through {@see HtmlModule} with the body shape supplied by
 * {@see \MyVendor\BeMart\Resource\Page\Admin\TradeLaw}. The EC-CUBE
 * residual-diff fidelity check is a follow-up gated on the EC-CUBE 4.3
 * reference clone (`tools/ec-cube-source/`).
 */
final class AdminTradeLawHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $module = new HtmlTestModule(new Meta('MyVendor\\BeMart', 'html'));
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testTradeLawRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/trade-law');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTradeLawPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/trade-law')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-contentsArea">',
            'id="tradeLawRow_1"',
            '特定商取引法設定',
            '注文確認画面に表示',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }
}
