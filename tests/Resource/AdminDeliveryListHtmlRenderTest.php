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
 * Phase 3 — HTML render check for the admin Delivery-list page (clean-room build).
 *
 * Render-smoke + L1/L2 functional verification against the clean-room
 * idea-admin-* template. The EC-CUBE reference-clone parity check is
 * retired to the ec-cube-parity-archived group (markTestSkipped).
 *
 * L1 — required fields and list output present in the rendered HTML.
 * L2 — form action/method/rel and link href/rel contracts verified.
 */
final class AdminDeliveryListHtmlRenderTest extends TestCase
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

    public function testDeliveryListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/delivery/delivery-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1/L2 functional check: idea-admin shell landmarks are present.
     */
    public function testDeliveryListRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin shell landmark missing: {$landmark}");
        }
    }

    /**
     * L2 — create form: action, method, and CSRF field.
     */
    public function testDeliveryListCreateFormContract(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery-list')->toString();

        $this->assertStringContainsString(
            'id="idea-delivery-create-form"',
            $html,
            'create form id required',
        );
        $this->assertStringContainsString(
            'action="/admin/delivery/delivery-list"',
            $html,
            'create form action must be /admin/delivery/delivery-list',
        );
        $this->assertStringContainsString(
            'method="post"',
            $html,
            'create form method must be POST',
        );
        $this->assertStringContainsString(
            'name="csrfToken"',
            $html,
            'csrfToken hidden field required',
        );
    }

    /**
     * L1 — required field: deliveryName input bound to the form contract.
     */
    public function testDeliveryListRequiredFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery-list')->toString();

        $this->assertStringContainsString(
            'id="delivery_name"',
            $html,
            'deliveryName field (id="delivery_name") required',
        );
    }

    /**
     * L2 — per-row edit link: href and rel="goDelivery" contract.
     *
     * Fake storage seeds at least one delivery row, so the table renders.
     */
    public function testDeliveryListRowEditLinkContract(): void
    {
        $ro  = $this->resource->get('page://self/admin/delivery/delivery-list');
        $html = $ro->toString();

        if (($ro->body['count'] ?? 0) === 0) {
            $this->markTestSkipped('Fake storage is empty — no rows to verify.');
        }

        $this->assertStringContainsString(
            'href="/admin/delivery/delivery?deliveryId=',
            $html,
            'per-row edit link href=/admin/delivery/delivery?deliveryId=… required',
        );
        $this->assertStringContainsString(
            'rel="goDelivery"',
            $html,
            'per-row edit link rel="goDelivery" required',
        );
    }

    /**
     * L2 — per-row delete button: data-delete-url and rel="doDeleteDelivery" on form.
     */
    public function testDeliveryListRowDeleteAffordanceContract(): void
    {
        $ro  = $this->resource->get('page://self/admin/delivery/delivery-list');
        $html = $ro->toString();

        if (($ro->body['count'] ?? 0) === 0) {
            $this->markTestSkipped('Fake storage is empty — no rows to verify.');
        }

        $this->assertStringContainsString(
            'data-delete-url="/admin/delivery/delivery?deliveryId=',
            $html,
            'delete button data-delete-url required',
        );
        $this->assertStringContainsString(
            'rel="doDeleteDelivery"',
            $html,
            'delete form rel="doDeleteDelivery" required',
        );
    }

    /**
     * L1 — list table structure: idea-admin-table present when rows exist.
     */
    public function testDeliveryListTableRendersRows(): void
    {
        $ro  = $this->resource->get('page://self/admin/delivery/delivery-list');
        $html = $ro->toString();

        if (($ro->body['count'] ?? 0) === 0) {
            $this->assertStringContainsString(
                'class="idea-admin-empty"',
                $html,
                'empty-state block required when count=0',
            );

            return;
        }

        $this->assertStringContainsString(
            'class="idea-admin-table"',
            $html,
            'idea-admin-table required when deliveries exist',
        );

        foreach ($ro->body['deliveries'] as $row) {
            $this->assertStringContainsString(
                $row['deliveryName'],
                $html,
                "deliveryName \"{$row['deliveryName']}\" must appear in the list",
            );
        }
    }

    /**
     * EC-CUBE reference-rendering parity check — retired.
     *
     * The clean-room template no longer derives from EC-CUBE DOM; the
     * reference-diff approach is inapplicable. Archived for historical
     * traceability.
     *
     * @group ec-cube-parity-archived
     */
    public function testDeliveryListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired: template is a clean-room idea-admin-* build '
            . 'and no longer tracks EC-CUBE DOM structure.',
        );
    }
}
