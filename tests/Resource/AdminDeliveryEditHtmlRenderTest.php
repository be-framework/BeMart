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
 * Phase 3 — HTML render check for the admin 配送方法設定（編集）
 * Setting/Shop Tier-2 page.
 *
 * Render-smoke + L1/L2 functional verification against the clean-room
 * idea-admin-* template. The EC-CUBE reference-clone parity check is
 * retired to the ec-cube-parity-archived group (markTestSkipped).
 *
 * L1 — required data fields and form inputs present in rendered HTML.
 * L2 — form action/method/rel and link href/rel contracts verified.
 */
final class AdminDeliveryEditHtmlRenderTest extends TestCase
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

    /**
     * Smoke: page renders a valid HTML document with 200 OK.
     */
    public function testDeliveryEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/delivery/delivery');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1/L2 — idea-admin shell landmarks are present (frame contract).
     */
    public function testDeliveryEditRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery')->toString();

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
     * L1 — required field: deliveryName input bound to the form contract.
     */
    public function testDeliveryEditDeliveryNameFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery')->toString();

        $this->assertStringContainsString(
            'id="delivery_name"',
            $html,
            'deliveryName field (id="delivery_name") required by AdminDeliveryForm contract',
        );
    }

    /**
     * L1 — visible checkbox rendered.
     */
    public function testDeliveryEditVisibleCheckboxPresent(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery')->toString();

        $this->assertStringContainsString(
            'id="delivery_visible"',
            $html,
            'visible checkbox (id="delivery_visible") required by AdminDeliveryForm contract',
        );
    }

    /**
     * L2 — new-record mode: form submits POST to /admin/delivery/delivery-list.
     *
     * When no deliveryId is supplied the resource renders a blank create form.
     * The form action must target the DeliveryList POST endpoint.
     */
    public function testDeliveryEditNewModeFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery')->toString();

        $this->assertStringContainsString(
            'action="/admin/delivery/delivery-list"',
            $html,
            'new-record form action must POST to /admin/delivery/delivery-list',
        );
        $this->assertStringContainsString(
            'method="post"',
            $html,
            'new-record form method must be POST',
        );
    }

    /**
     * L2 — CSRF hidden field present in the edit form.
     */
    public function testDeliveryEditCsrfFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery')->toString();

        $this->assertStringContainsString(
            'name="csrfToken"',
            $html,
            'csrfToken hidden field required',
        );
    }

    /**
     * L2 — edit mode: form PUT action and delete affordance contracts.
     *
     * Requires a known deliveryId from Fake storage. Skipped when Fake has no seed.
     */
    public function testDeliveryEditModeFormAndDeleteAffordance(): void
    {
        // Get a known deliveryId from the list endpoint
        $listRo = $this->resource->get('page://self/admin/delivery/delivery-list');
        if (($listRo->body['count'] ?? 0) === 0) {
            $this->markTestSkipped('Fake storage is empty — cannot verify edit-mode contracts.');
        }

        $firstId = $listRo->body['deliveries'][0]['deliveryId'];
        $html = $this->resource->get('page://self/admin/delivery/delivery', ['deliveryId' => $firstId])
            ->toString();

        // PUT action
        $this->assertStringContainsString(
            '/admin/delivery/delivery?deliveryId=',
            $html,
            'edit-mode form action must include deliveryId query param',
        );
        $this->assertStringContainsString(
            '_method=put',
            $html,
            'edit-mode form must carry _method=put tunnel field',
        );

        // Delete form
        $this->assertStringContainsString(
            'rel="doDeleteDelivery"',
            $html,
            'delete form rel="doDeleteDelivery" required',
        );
        $this->assertStringContainsString(
            '_method=delete',
            $html,
            'delete form must carry _method=delete tunnel field',
        );
    }

    /**
     * L2 — back link navigates to the delivery list.
     */
    public function testDeliveryEditBackLinkHref(): void
    {
        $html = $this->resource->get('page://self/admin/delivery/delivery')->toString();

        $this->assertStringContainsString(
            'href="/admin/delivery/delivery-list"',
            $html,
            'back link href must point to /admin/delivery/delivery-list',
        );
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
    public function testDeliveryEditPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired: template is a clean-room idea-admin-* build '
            . 'and no longer tracks EC-CUBE DOM structure.',
        );
    }
}
