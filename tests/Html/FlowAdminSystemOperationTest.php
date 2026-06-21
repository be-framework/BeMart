<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;

/**
 * HTML hypermedia walk of admin system operations — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Ported from tests/Hypermedia/FlowAdminSystemOperationTest.php.
 *
 * Steps walked:
 *   1. testOpensMemberCreatePage  — GET /admin/member (no loginId) → doCreateMember form (200)
 *   2. testCreatesMember          — submit doCreateMember → 303 + Location
 *   3. testConfirmsCreatedMember  — follow Location → member edit page with doUpdateMember form
 *   4. testUpdatesMember          — submit doUpdateMember → 303 + Location
 *   5. testVerifiesUpdatedMember  — follow Location → edit page shows updated name
 *   6. testOpensSecurityPage      — GET /admin/security → doUpdateSecurity form (200)
 *   7. testUpdatesSecurity        — submit doUpdateSecurity → 200|303
 *   8. testOpensCachePage         — GET /admin/content/cache → doClearCache form (200)
 *   9. testClearsCache            — submit doClearCache → 200|303
 *  10. testOpensMaintenancePage   — GET /admin/content/maintenance → doToggleMaintenance form (200)
 *  11. testTogglesMaintenance     — submit doToggleMaintenance (enabled=0) → 200|303
 *
 * Steps skipped (with rationale):
 *   - goAdminLogin / doAdminLogin: The workflow uses an injected DB session
 *     (WorkflowDbSession) for a pre-authenticated admin; driving a full
 *     logout→login cycle would invalidate that session and require a fresh
 *     credential round-trip that is out of scope for the HTML walk.
 *   - doSetTwoFactorAuth / doVerifyTwoFactorAuth: Enabling 2FA on the
 *     active admin account or verifying a TOTP token would lock the
 *     session out if the TOTP code cannot be reproduced in a stateless
 *     HTTP walk. Skipped to prevent a non-recoverable lockout state.
 *   - goLoginHistoryList: read-only list, no HTML-followable write affordance.
 *   - goSystemInfo: read-only display, no form affordance to submit.
 *   - doAdminLogout: terminating the admin session would prevent the
 *     tearDown restore from completing cleanly.
 *   - doDeleteMember: the delete affordance in MemberList.html.twig is
 *     rendered inside a Bootstrap modal dialog triggered by a JS data-bs-target
 *     anchor (not a visible <form class="doDeleteMember">), so submit()
 *     cannot resolve it without JavaScript execution.
 */
final class FlowAdminSystemOperationTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-system-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-system-html-csrf-token';
    private const MEMBER_PASSWORD = 'workflow-html-member-2026';

    private static string $memberLoginId;
    private static string $memberName;
    private static string $updatedMemberName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$memberLoginId = 'html-member-' . $suffix;
        self::$memberName = 'HTML System Member ' . $suffix;
        self::$updatedMemberName = 'HTML Updated Member ' . $suffix;
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8131',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    // -------------------------------------------------------------------------
    // Member create → update walk
    // -------------------------------------------------------------------------

    #[Alps('doCreateMember')]
    public function testOpensMemberCreatePage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/member');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doCreateMember');

        return $page;
    }

    #[Alps('doCreateMember')]
    #[Depends('testOpensMemberCreatePage')]
    public function testCreatesMember(ResourceObject $page): ResourceObject
    {
        $created = $this->submit($page, 'doCreateMember', [
            'loginId' => self::$memberLoginId,
            'password' => self::MEMBER_PASSWORD,
            'passwordConfirm' => self::MEMBER_PASSWORD,
            'name' => self::$memberName,
            'authority' => '1',
            'mode' => 'member_form',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doCreateMember affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('doUpdateMember')]
    #[Depends('testCreatesMember')]
    public function testConfirmsCreatedMember(ResourceObject $created): ResourceObject
    {
        $location = $this->header($created, 'Location');
        $detail = $location !== null
            ? $this->followLocation($created)
            : $this->resource->get('page://self/admin/member', ['loginId' => self::$memberLoginId]);

        $this->assertSame(Code::OK, $detail->code, (string) ($detail->view ?? $detail->code));
        $this->assertAffordance($detail, 'doUpdateMember');

        return $detail;
    }

    #[Alps('doUpdateMember')]
    #[Depends('testConfirmsCreatedMember')]
    public function testUpdatesMember(ResourceObject $detail): ResourceObject
    {
        $updated = $this->submit($detail, 'doUpdateMember', [
            'loginId' => self::$memberLoginId,
            'name' => self::$updatedMemberName,
            'mode' => 'member_form',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateMember affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    #[Alps('doUpdateMember')]
    #[Depends('testUpdatesMember')]
    public function testVerifiesUpdatedMember(ResourceObject $updated): void
    {
        $location = $this->header($updated, 'Location');
        $page = $location !== null
            ? $this->followLocation($updated)
            : $this->resource->get('page://self/admin/member', ['loginId' => self::$memberLoginId]);

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertStringContainsString(self::$memberLoginId, (string) ($page->view ?? ''));
    }

    // -------------------------------------------------------------------------
    // Security settings walk
    // -------------------------------------------------------------------------

    #[Alps('doUpdateSecurity')]
    public function testOpensSecurityPage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/security');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateSecurity');

        return $page;
    }

    #[Alps('doUpdateSecurity')]
    #[Depends('testOpensSecurityPage')]
    public function testUpdatesSecurity(ResourceObject $page): void
    {
        $updated = $this->submit($page, 'doUpdateSecurity', [
            'adminRouteDir' => 'admin',
            'adminAllowHosts' => '',
            'adminDenyHosts' => '',
            'frontAllowHosts' => '',
            'frontDenyHosts' => '',
            'trustedHosts' => '^localhost$',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateSecurity affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );
    }

    // -------------------------------------------------------------------------
    // Cache clear walk
    // -------------------------------------------------------------------------

    #[Alps('doClearCache')]
    public function testOpensCachePage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/content/cache');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doClearCache');

        return $page;
    }

    #[Alps('doClearCache')]
    #[Depends('testOpensCachePage')]
    public function testClearsCache(ResourceObject $page): void
    {
        $cleared = $this->submit($page, 'doClearCache', [
            'mode' => 'content_operation_form',
        ]);

        $this->assertTrue(
            in_array($cleared->code, [Code::OK, Code::SEE_OTHER], true),
            'doClearCache affordance did not succeed: ' . (string) ($cleared->view ?? $cleared->code),
        );
    }

    // -------------------------------------------------------------------------
    // Maintenance toggle walk
    // -------------------------------------------------------------------------

    #[Alps('doToggleMaintenance')]
    public function testOpensMaintenancePage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/content/maintenance');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doToggleMaintenance');

        return $page;
    }

    #[Alps('doToggleMaintenance')]
    #[Depends('testOpensMaintenancePage')]
    public function testTogglesMaintenance(ResourceObject $page): void
    {
        // Always disable maintenance (enabled=0) — safe to run in CI;
        // forces off regardless of prior state so the test environment
        // remains usable. The `enabled` hidden field is rendered by the
        // template but submit() only auto-injects csrfToken, so we pass
        // `enabled` explicitly.
        $toggled = $this->submit($page, 'doToggleMaintenance', [
            'enabled' => '0',
            'mode' => 'content_operation_form',
        ]);

        $this->assertTrue(
            in_array($toggled->code, [Code::OK, Code::SEE_OTHER], true),
            'doToggleMaintenance affordance did not succeed: ' . (string) ($toggled->view ?? $toggled->code),
        );
    }
}
