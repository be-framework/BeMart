<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function getenv;
use function putenv;

/**
 * Wave 9 — resource-layer coverage for the admin Tag endpoints.
 */
final class AdminTagResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
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
    }

    private function seed(string $name): string
    {
        // Static Ray.FakeQuery fixture, not a mutable seed.
        unset($name);

        return 'tg-new';
    }

    public function testListIncludesSeed(): void
    {
        $ro = $this->resource->get('page://self/admin/tag/tag-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/tag/tag-list');
    }

    public function testCreateHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => '限定',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('限定', $ro->body['tagName']);
    }

    public function testCreateHtmlContextRedirectsToTagList(): void
    {
        $previousContext = getenv('APP_CONTEXT');
        putenv('APP_CONTEXT=html-test-hal-app');
        try {
            $ro = $this->resource->post('page://self/admin/tag/tag-list', [
                'tagName' => '限定',
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
        } finally {
            putenv($previousContext === false ? 'APP_CONTEXT' : 'APP_CONTEXT=' . $previousContext);
        }

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/tag/tag-list', $ro->headers['Location']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => '限定',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('Tmp');
        $ro = $this->resource->delete('page://self/admin/tag/tag', [
            'tagId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
    }

    public function testDeleteHtmlContextRedirectsToTagList(): void
    {
        $id = $this->seed('Tmp');
        $previousContext = getenv('APP_CONTEXT');
        putenv('APP_CONTEXT=html-test-hal-app');
        try {
            $ro = $this->resource->delete('page://self/admin/tag/tag', [
                'tagId' => $id,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
        } finally {
            putenv($previousContext === false ? 'APP_CONTEXT' : 'APP_CONTEXT=' . $previousContext);
        }

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/tag/tag-list', $ro->headers['Location']);
    }

    public function testDeleteUnknownReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\TagNotFoundException::class);

        $this->resource->delete('page://self/admin/tag/tag', [
            'tagId' => 'nonexistent',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
