<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminMasterDataForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin マスタデータ管理 Tier-2 page.
 *
 * The resource is a GET renderer backed by the Be admin-master
 * registry: it exposes selectable master types plus `{id, name}` rows.
 * An unknown master type maps to 400; an anonymous admin gets 403.
 */
final class AdminMasterDataResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsMasterTypesAndRows(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/master-data');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminMasterDataForm::class, $ro->body['form']);
        $this->assertSame('tag', $ro->body['selectedMaster']);
        $this->assertNotEmpty($ro->body['masterTypes']);
        $this->assertIsArray($ro->body['rows']);
    }

    public function testOnGetSelectsRequestedMasterType(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/master-data', ['masterType' => 'delivery']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('delivery', $ro->body['selectedMaster']);
    }

    public function testOnGetUnknownMasterTypeReturns400(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $this->expectException(\MyVendor\BeMart\Be\Exception\MasterTypeFormatException::class);

        $this->resource->get('page://self/admin/master-data', ['masterType' => 'no-such-master']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/master-data');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
