<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function str_contains;

/**
 * Phase 3 ALPS-audit remediation — JSON resource coverage for the two
 * generic admin-list transitions doSortNoMove / doToggleVisible.
 */
#[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
final class AdminMasterListTransitionsResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private TagStorageInterface $tags;
    private DeliveryStorageInterface $deliveries;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->tags = $injector->getInstance(TagStorageInterface::class);
        $this->deliveries = $injector->getInstance(DeliveryStorageInterface::class);
    }

    // ---- doSortNoMove --------------------------------------------------

    public function testSortNoMoveHappyPath(): void
    {
        $this->tags->put(new TagEntity('t1', 'New'));

        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'tag',
            'rowId' => 't1',
            'sortNo' => 9,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('tag', $ro->body['masterType']);
        $this->assertSame(9, $ro->body['sortNo']);
        $this->assertSame(9, $this->database->sortNoOf('tag', 't1'));
    }

    public function testSortNoMoveRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->tags->put(new TagEntity('t1', 'New'));

        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'tag',
            'rowId' => 't1',
            'sortNo' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testSortNoMoveUnknownRowReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'tag',
            'rowId' => 'no-such-row',
            'sortNo' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSortNoMoveUnknownMasterReturns400(): void
    {
        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'bogusMaster',
            'rowId' => 'x',
            'sortNo' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testSortNoMoveOnNewsReturns400(): void
    {
        // `news` is a known master but has no sort_no column.
        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'news',
            'rowId' => 'nw-welcome',
            'sortNo' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    // ---- doToggleVisible ----------------------------------------------

    public function testToggleVisibleHappyPath(): void
    {
        $this->deliveries->put(new DeliveryEntity('d1', '宅配便', true));

        $ro = $this->resource->put('page://self/admin/toggle-visible', [
            'masterType' => 'delivery',
            'rowId' => 'd1',
            'visible' => false,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('delivery', $ro->body['masterType']);
        $this->assertFalse($ro->body['visible']);
        $row = $this->deliveries->item('d1');
        $this->assertNotNull($row);
        $this->assertFalse($row->visible);
    }

    public function testToggleVisibleRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->deliveries->put(new DeliveryEntity('d1', '宅配便', true));

        $ro = $this->resource->put('page://self/admin/toggle-visible', [
            'masterType' => 'delivery',
            'rowId' => 'd1',
            'visible' => false,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testToggleVisibleUnknownRowReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/toggle-visible', [
            'masterType' => 'delivery',
            'rowId' => 'no-such-row',
            'visible' => true,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testToggleVisibleOnTagReturns400(): void
    {
        // `tag` is a known master but dtb_tag has no visible column.
        $this->tags->put(new TagEntity('t1', 'New'));

        $ro = $this->resource->put('page://self/admin/toggle-visible', [
            'masterType' => 'tag',
            'rowId' => 't1',
            'visible' => false,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }
}
