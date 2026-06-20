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
 * HTML hypermedia walk of the admin authority-role editor — driven entirely by
 * the rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Ported from tests/Hypermedia/FlowAdminAuthorityRoleRulesTest.php.
 *
 * Steps walked:
 *   1. testOpensAuthorityRolePage   — GET /admin/authority-role (200 + doUpdateAuthorityRole form)
 *   2. testUpdatesAuthorityRoles    — submit doUpdateAuthorityRole form -> 200|303
 *   3. testVerifiesUpdatedRules     — follow Location -> page renders the saved deny URL
 *
 * Steps skipped:
 *   - None: the Hypermedia test is a 3-step read-then-write-then-verify walk,
 *     and all three steps are HTML-followable via the rendered form affordance.
 */
final class FlowAdminAuthorityRoleRulesTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-authority-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-authority-html-csrf-token';

    private static string $denyUrl;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$denyUrl = '/admin/workflow-deny-' . bin2hex(random_bytes(4));
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
            '127.0.0.1:8121',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('doUpdateAuthorityRole')]
    public function testOpensAuthorityRolePage(): ResourceObject
    {
        $page = $this->resource->get('page://self/admin/authority-role');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        $this->assertAffordance($page, 'doUpdateAuthorityRole');

        return $page;
    }

    #[Alps('doUpdateAuthorityRole')]
    #[Depends('testOpensAuthorityRolePage')]
    public function testUpdatesAuthorityRoles(ResourceObject $page): ResourceObject
    {
        $updated = $this->submit($page, 'doUpdateAuthorityRole', [
            'AuthorityRoles' => [
                ['Authority' => 1, 'deny_url' => self::$denyUrl],
            ],
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::SEE_OTHER], true),
            'doUpdateAuthorityRole affordance did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    #[Alps('doUpdateAuthorityRole')]
    #[Depends('testUpdatesAuthorityRoles')]
    public function testVerifiesUpdatedRules(ResourceObject $updated): void
    {
        $location = $this->header($updated, 'Location');
        $page = $location !== null
            ? $this->followLocation($updated)
            : $this->resource->get('page://self/admin/authority-role');

        $this->assertSame(Code::OK, $page->code, (string) ($page->view ?? $page->code));
        // Assert the persisted deny_url is rendered as the rule input's value
        // (the descriptor's rendered value), not merely present somewhere on the
        // page — same readback contract as the Http twin.
        $this->assertStringContainsString('value="' . self::$denyUrl . '"', (string) ($page->view ?? ''));
    }
}
