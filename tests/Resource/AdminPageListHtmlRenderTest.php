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

use function str_contains;

/**
 * Functional / semantic render verification for the admin Page-list HTML template.
 *
 * Checks three layers:
 *   L1 — required data is present in output (pageName, pageUrl, pageFileName)
 *   L2 — actions are reachable at the correct href / method / rel
 *   Frame — idea-admin shell landmarks present
 *
 * EC-CUBE reference-parity tests have been removed; this endpoint is
 * a clean-room redesign (idea-admin design language, no EC-CUBE markup).
 */
final class AdminPageListHtmlRenderTest extends TestCase
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

    // ── Frame landmark ────────────────────────────────────────────────────────

    /** The response is a full HTML document with the idea-admin shell. */
    public function testFrameLandmarks(): void
    {
        $ro = $this->resource->get('page://self/admin/page/page-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    // ── L1: required data present ─────────────────────────────────────────────

    /**
     * The fake seed includes a system page (ホームページ / homepage / index).
     * Its pageName, pageUrl, and pageFileName must appear in the output.
     */
    public function testL1SystemPageDataRendered(): void
    {
        $html = $this->resource->get('page://self/admin/page/page-list')->toString();

        $this->assertStringContainsString('ホームページ', $html, 'pageName must render');
        $this->assertStringContainsString('homepage', $html, 'pageUrl must render');
        $this->assertStringContainsString('index.twig', $html, 'pageFileName with .twig suffix must render');
    }

    /**
     * The count value from the resource body must appear somewhere in the
     * rendered output (KPI or footer).
     */
    public function testL1CountRendered(): void
    {
        $ro  = $this->resource->get('page://self/admin/page/page-list');
        $cnt = (string) ($ro->body['count'] ?? '');
        $html = $ro->toString();

        if ($cnt !== '' && $cnt !== '0') {
            $this->assertStringContainsString($cnt, $html, 'count must appear in output');
        } else {
            $this->assertTrue(true); // empty list is acceptable
        }
    }

    // ── L2: actions / links ───────────────────────────────────────────────────

    /**
     * The "create page" form must post to the correct endpoint.
     * rel="doCreatePage" is expressed implicitly via form action.
     */
    public function testL2CreateFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/page/page-list')->toString();

        $this->assertStringContainsString('action="/admin/page/page-list"', $html, 'create form must post to page-list');
        $this->assertStringContainsString('method="post"', $html, 'create form must use POST');
        $this->assertStringContainsString('name="pageName"', $html, 'pageName field must be present');
        $this->assertStringContainsString('name="pageUrl"', $html, 'pageUrl field must be present');
        $this->assertStringContainsString('name="pageFileName"', $html, 'pageFileName field must be present');
    }

    /**
     * Each page row must contain a goPage link pointing to the edit endpoint.
     */
    public function testL2GoPageLinks(): void
    {
        $ro   = $this->resource->get('page://self/admin/page/page-list');
        $html = $ro->toString();

        foreach (($ro->body['pages'] ?? []) as $page) {
            $pageId = (string) ($page['pageId'] ?? '');
            if ($pageId === '') {
                continue;
            }

            $this->assertTrue(
                str_contains($html, '/admin/page/page?pageId=' . $pageId),
                "goPage link for pageId={$pageId} must be present",
            );
        }
    }

    /**
     * Delete affordance must exist for user pages (pageEditType 0) and must
     * be absent for system pages (pageEditType >= 2).
     */
    public function testL2DeleteAffordanceRespectsEditType(): void
    {
        $ro   = $this->resource->get('page://self/admin/page/page-list');
        $html = $ro->toString();

        foreach (($ro->body['pages'] ?? []) as $page) {
            $pageId   = (string) ($page['pageId'] ?? '');
            $editType = (int) ($page['pageEditType'] ?? -1);
            if ($pageId === '') {
                continue;
            }

            $hasDelete = str_contains($html, 'del-dlg-' . $pageId);

            if ($editType === 0) {
                $this->assertTrue($hasDelete, "Delete dialog must exist for user page pageId={$pageId}");
            } elseif ($editType >= 2) {
                $this->assertFalse($hasDelete, "Delete dialog must NOT exist for system page pageId={$pageId}");
            }
        }
    }

    /**
     * The delete form must submit to /admin/page/page via POST + _method=delete
     * and carry the pageId of the target user page.
     */
    public function testL2DeleteFormMethod(): void
    {
        $ro   = $this->resource->get('page://self/admin/page/page-list');
        $html = $ro->toString();

        foreach (($ro->body['pages'] ?? []) as $page) {
            $pageId   = (string) ($page['pageId'] ?? '');
            $editType = (int) ($page['pageEditType'] ?? -1);
            if ($pageId === '' || $editType !== 0) {
                continue;
            }

            $this->assertStringContainsString('action="/admin/page/page"', $html, 'delete form must target /admin/page/page');
            $this->assertStringContainsString('name="_method" value="delete"', $html, 'delete must tunnel via _method=delete');
            $this->assertStringContainsString('name="pageId" value="' . $pageId . '"', $html, 'pageId must be in delete form');
            // Only check the first user page to avoid repetitive assertions.
            break;
        }
    }

    // ── EC-CUBE parity tests (archived) ──────────────────────────────────────

    /**
     * The PageList template is a clean-room redesign (idea-admin design
     * language). EC-CUBE reference markup comparison is not applicable.
     *
     * @group ec-cube-parity-archived
     */
    public function testPageListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check archived: PageList.html.twig is a clean-room '
            . 'redesign using the idea-admin design language. '
            . 'EC-CUBE c-* / Bootstrap class structure is intentionally absent.',
        );
    }
}
