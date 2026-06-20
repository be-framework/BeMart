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
 * HTML hypermedia walk of the admin tag editor — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Ported from tests/Hypermedia/FlowAdminTagMaintenanceTest.php.
 *
 * Steps walked:
 *   1. testOpensAdminTagList      — GET page://self/admin/tag/tag-list (200 + doCreateTag form)
 *   2. testCreatesTag             — submit doCreateTag form -> 201|303
 *   3. testFindsCreatedTag        — follow Location (or re-GET list), assertStringContainsString tagName
 *
 * Steps skipped:
 *   - doDeleteTag: The template renders delete as a JS modal anchor (`data-post-action="delete"`,
 *     `data-url`) wired by JS, not as a `<form class="doDeleteTag">`. There is no
 *     HTML-followable form affordance for deletion, so it cannot be exercised via submit().
 *     Likewise doSortNo uses an AJAX endpoint — not a rendered form affordance.
 */
final class FlowAdminTagHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-tag-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-tag-html-csrf-token';

    private static string $tagName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$tagName = 'HTML Tag ' . bin2hex(random_bytes(4));
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
            '127.0.0.1:8118',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goTagList')]
    public function testOpensAdminTagList(): ResourceObject
    {
        $list = $this->resource->get('page://self/admin/tag/tag-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertAffordance($list, 'doCreateTag');

        return $list;
    }

    #[Alps('doCreateTag')]
    #[Depends('testOpensAdminTagList')]
    public function testCreatesTag(ResourceObject $list): ResourceObject
    {
        $created = $this->submit($list, 'doCreateTag', [
            'tagName' => self::$tagName,
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'doCreateTag affordance did not succeed: ' . (string) ($created->view ?? $created->code),
        );

        return $created;
    }

    #[Alps('goTagList')]
    #[Depends('testCreatesTag')]
    public function testFindsCreatedTag(ResourceObject $created): void
    {
        $location = $this->header($created, 'Location');
        $list = $location !== null
            ? $this->followLocation($created)
            : $this->resource->get('page://self/admin/tag/tag-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(self::$tagName, (string) ($list->view ?? ''));
    }
}
