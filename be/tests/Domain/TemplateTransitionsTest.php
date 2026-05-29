<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TemplateDeleted;
use MyVendor\BeMart\Be\Final\TemplateDownloaded;
use MyVendor\BeMart\Be\Final\TemplateInstalled;
use MyVendor\BeMart\Be\Final\TemplateSelected;
use MyVendor\BeMart\Be\Input\DeleteTemplateInput;
use MyVendor\BeMart\Be\Input\DownloadTemplateInput;
use MyVendor\BeMart\Be\Input\InstallTemplateInput;
use MyVendor\BeMart\Be\Input\SelectTemplateInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class TemplateTransitionsTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

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

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testSelectExistingTemplate(): void
    {
        $final = ($this->becoming)(new SelectTemplateInput(templateId: 'default'));
        $this->assertInstanceOf(TemplateSelected::class, $final);
        $this->assertSame('default', $final->templateId);
    }

    public function testSelectUnknownTemplateRaisesNotFound(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        ($this->becoming)(new SelectTemplateInput(templateId: 'no-such'));
    }

    public function testDeleteExistingTemplate(): void
    {
        $final = ($this->becoming)(new DeleteTemplateInput(templateId: 'default'));
        $this->assertInstanceOf(TemplateDeleted::class, $final);
    }

    public function testDownloadExistingTemplate(): void
    {
        $final = ($this->becoming)(new DownloadTemplateInput(templateId: 'default'));
        $this->assertInstanceOf(TemplateDownloaded::class, $final);
        $this->assertGreaterThan(0, $final->archive->size);
    }

    public function testInstallTemplate(): void
    {
        $final = ($this->becoming)(new InstallTemplateInput(templateCode: 'mytheme', templateName: 'My Theme'));
        $this->assertInstanceOf(TemplateInstalled::class, $final);
        $this->assertNotSame('', $final->templateId);
    }

    public function testInstallRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new InstallTemplateInput(templateCode: 'x', templateName: 'y'));
    }
}
