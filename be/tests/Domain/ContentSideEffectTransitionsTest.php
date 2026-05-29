<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CacheCleared;
use MyVendor\BeMart\Be\Final\ContentCssUpdated;
use MyVendor\BeMart\Be\Final\ContentJsUpdated;
use MyVendor\BeMart\Be\Final\MaintenanceToggled;
use MyVendor\BeMart\Be\Input\ClearCacheInput;
use MyVendor\BeMart\Be\Input\ToggleMaintenanceInput;
use MyVendor\BeMart\Be\Input\UpdateContentCssInput;
use MyVendor\BeMart\Be\Input\UpdateContentJsInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCacheClearer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCustomizeAssetWriter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMaintenanceMode;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Content/file side-effect transitions (doClearCache / doUpdateContentCss
 * / doUpdateContentJs / doToggleMaintenance). Each delegates its
 * filesystem/runtime side-effect to a boundary fake; the AUTHZ ladder
 * (admin session required) is shared.
 */
final class ContentSideEffectTransitionsTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private Injector $injector;
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    public function testClearCache(): void
    {
        $final = ($this->becoming)(new ClearCacheInput());
        $this->assertInstanceOf(CacheCleared::class, $final);
        $this->assertSame(1, $this->injector->getInstance(FakeCacheClearer::class)->clears);
    }

    public function testUpdateContentCss(): void
    {
        $final = ($this->becoming)(new UpdateContentCssInput(css: 'body{color:red}'));
        $this->assertInstanceOf(ContentCssUpdated::class, $final);
        $this->assertSame('body{color:red}', $this->injector->getInstance(FakeCustomizeAssetWriter::class)->css);
    }

    public function testUpdateContentJs(): void
    {
        $final = ($this->becoming)(new UpdateContentJsInput(js: 'console.log(1)'));
        $this->assertInstanceOf(ContentJsUpdated::class, $final);
        $this->assertSame('console.log(1)', $this->injector->getInstance(FakeCustomizeAssetWriter::class)->js);
    }

    public function testToggleMaintenance(): void
    {
        $final = ($this->becoming)(new ToggleMaintenanceInput(enabled: true));
        $this->assertInstanceOf(MaintenanceToggled::class, $final);
        $this->assertTrue($this->injector->getInstance(FakeMaintenanceMode::class)->enabled);
    }

    public function testClearCacheRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ClearCacheInput());
    }

    public function testToggleMaintenanceRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ToggleMaintenanceInput(enabled: true));
    }
}
