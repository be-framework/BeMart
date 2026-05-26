<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Resource\Page\Admin\Content\Css;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\WebFormModule\FormFactory;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Admin CSS management should expose the actual customize.css file instead
 * of an empty ACE shell.
 */
final class AdminContentCssHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FormFactory $formFactory;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->formFactory = $injector->getInstance(FormFactory::class);
    }

    public function testCssManagementRendersCustomizeCssInVisibleTextarea(): void
    {
        $ro = $this->resource->get('page://self/admin/content/css');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('CSS管理', $html);
        $this->assertStringContainsString('name="css"', $html);
        $this->assertStringContainsString('id="form_css"', $html);
        $this->assertStringContainsString('カスタマイズ用CSS', $html);
        $this->assertStringContainsString('font-family: ui-monospace', $html);
        $this->assertStringNotContainsString('<div id="editor"', $html);
        $this->assertStringNotContainsString('style="display: none"', $html);
    }

    public function testCssResourceReadsAndWritesCustomizeCssFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bemart-css-');
        self::assertIsString($tmp);
        file_put_contents($tmp, '/* initial css */' . "\n");

        try {
            $resource = new Css(new FakeAdminSession(self::TEST_ADMIN_ID), $this->formFactory, $tmp);

            $get = $resource->onGet();
            $this->assertSame(Code::OK, $get->code);
            $this->assertSame('/* initial css */' . "\n", $get->body['css']);

            $post = $resource->onPost('body { color: #123456; }' . "\n");
            $this->assertSame(Code::OK, $post->code);
            $this->assertSame('body { color: #123456; }' . "\n", file_get_contents($tmp));
            $this->assertSame('CSSを更新しました。', $post->body['message']);
        } finally {
            @unlink($tmp);
        }
    }

    public function testCssManagementRejectsAnonymousAdmin(): void
    {
        $resource = new Css(new FakeAdminSession(null), $this->formFactory, __FILE__);

        $ro = $resource->onGet();

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
