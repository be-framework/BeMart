<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Dev\Http\AbstractWorkflowTest;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function array_column;
use function assert;
use function bin2hex;
use function random_bytes;

final class FlowAdminAuthorityRoleRulesTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-authority-role-rules';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-authority-role-csrf-token';

    private static WorkflowDbSession|null $dbSession = null;
    private static string $denyUrl;

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

        return self::$dbSession->resource();
    }

    #[Alps('doUpdateAuthorityRole')]
    public function testAuthorityRoleRules(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/authority-role');

        $this->assertSame(Code::OK, $response->code);
        $this->assertNotEmpty($this->bodyValue($response, 'rules'));

        return $response;
    }

    #[Alps('doUpdateAuthorityRole')]
    #[Depends('testAuthorityRoleRules')]
    public function testUpdatesAuthorityRoleRules(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post($this->linkHref($response, 'doUpdateAuthorityRole'), [
            'AuthorityRoles' => [
                ['Authority' => 1, 'deny_url' => self::$denyUrl],
            ],
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertContains($updated->code, [Code::OK, Code::SEE_OTHER]);
        $this->assertSame('doUpdateAuthorityRole', $this->bodyValue($updated, 'transitionId'));
        $this->assertSame(1, $this->bodyValue($updated, 'count'));
        $this->assertSame('/admin/authority-role', $this->header($updated, 'Location'));

        return $updated;
    }

    #[Alps('doUpdateAuthorityRole')]
    #[Depends('testUpdatesAuthorityRoleRules')]
    public function testReadsUpdatedAuthorityRoleRules(ResourceObject $response): void
    {
        $read = $this->followLocation($response);

        $rules = $this->bodyValue($read, 'rules');
        $this->assertIsArray($rules);
        $this->assertContains(self::$denyUrl, array_column($rules, 'denyUrl'));
    }
}
