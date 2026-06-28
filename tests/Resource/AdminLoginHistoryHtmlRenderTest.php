<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * HTML render tests for the admin Login-history page (goLoginHistoryList).
 *
 * Setting/System section, DATA/LIST page rendered via idea-admin-* design
 * language. Three active tests:
 *
 *   testLoginHistoryRendersAsHtmlDocument  — shell / HTTP contract
 *   testLoginHistoryRendersRequiredMarkup  — L1 table landmarks + L2 link href/rel
 *   testLoginHistoryRendersSeededRows      — L1 data rows from fake storage
 *
 * The EC-CUBE structural parity test (diff-based honesty check) is archived
 * at {@see testLoginHistoryHtmlMatchesEcCubeRenderingWithinResidualAllowlist}.
 * It is skipped unconditionally; CI can opt-in with --group ec-cube-parity-archived.
 */
final class AdminLoginHistoryHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testLoginHistoryRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/login-history');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1 — Table landmarks are present (data grid structure).
     * L2 — Navigation links carry href and rel derived from resource #[Link].
     */
    public function testLoginHistoryRendersRequiredMarkup(): void
    {
        $html = $this->resource->get('page://self/admin/login-history')->toString();

        // L1: idea-admin table structure
        foreach ([
            'class="idea-admin-table"',
            'class="idea-admin-table-wrap"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "idea-admin landmark missing: {$needle}");
        }

        // L2: navigation links — href and rel from #[Link(rel:'goSecurity')] and #[Link(rel:'goMemberList')]
        $this->assertStringContainsString('href="/admin/security"', $html);
        $this->assertStringContainsString('rel="goSecurity"', $html);
        $this->assertStringContainsString('href="/admin/member-list"', $html);
        $this->assertStringContainsString('rel="goMemberList"', $html);
    }

    /**
     * L1 — Seeded login-attempt rows (from fake storage) are rendered.
     */
    public function testLoginHistoryRendersSeededRows(): void
    {
        $html = $this->resource->get('page://self/admin/login-history')->toString();

        $this->assertStringContainsString('192.0.2.10', $html);
        $this->assertStringContainsString('test-admin', $html);
        $this->assertStringContainsString('成功', $html);
        $this->assertStringContainsString('失敗', $html);
    }

    /**
     * EC-CUBE structural parity test — archived after idea-admin rebuild.
     *
     * The template now uses the idea-admin-* design language and no longer
     * mirrors EC-CUBE's c-* / ec-* / Bootstrap frame. The diff-based honesty
     * check is no longer meaningful; CI can opt-in with --group ec-cube-parity-archived.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testLoginHistoryHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE structural parity check archived: template rebuilt in idea-admin-* design language.'
        );
    }
}
