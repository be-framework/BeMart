<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeMailTemplateStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

/**
 * Wave 9ι — goMailTemplateList resource coverage. Safe read on the
 * same URI as Wave 8ε doUpdateMailTemplate.
 */
final class AdminMailTemplateListResourceTest extends TestCase
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
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsSeededTemplates(): void
    {
        $ro = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(2, $ro->body['count']);

        $ids = array_column($ro->body['mailTemplates'], 'mailTemplateId');
        $this->assertContains(FakeMailTemplateStorage::SEED_ORDER_CONFIRM_ID, $ids);
        $this->assertContains(FakeMailTemplateStorage::SEED_REGISTER_THANKS_ID, $ids);

        // Shape check — required projection fields are present.
        foreach ($ro->body['mailTemplates'] as $row) {
            $this->assertArrayHasKey('mailTemplateId', $row);
            $this->assertArrayHasKey('mailTemplateName', $row);
            $this->assertArrayHasKey('fileName', $row);
            $this->assertArrayHasKey('mailSubject', $row);
            $this->assertArrayHasKey('mailBody', $row);
        }
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
