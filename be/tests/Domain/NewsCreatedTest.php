<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\NewsUrlFormatException;
use MyVendor\BeMart\Be\Final\NewsCreated;
use MyVendor\BeMart\Be\Input\CreateNewsInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * doCreateNews — the newsUrl scheme contract. The admin news list renders
 * newsUrl as a bare `href`, so a `javascript:` value must never reach
 * storage.
 */
final class NewsCreatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private const PUBLISH_DATE = '2026-01-01T00:00:00+09:00';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
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

        $this->becoming = (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(BecomingInterface::class);
    }

    private function create(string|null $newsUrl): object
    {
        return ($this->becoming)(new CreateNewsInput(
            newsTitle: 'スキーマ検証',
            publishDate: self::PUBLISH_DATE,
            newsUrl: $newsUrl,
        ));
    }

    public function testHttpsUrlIsAccepted(): void
    {
        $final = $this->create('https://example.com/news/1');

        $this->assertInstanceOf(NewsCreated::class, $final);
        $this->assertSame('https://example.com/news/1', $final->newsUrl);
    }

    public function testSiteRelativePathIsAccepted(): void
    {
        $final = $this->create('/products/list');

        $this->assertInstanceOf(NewsCreated::class, $final);
        $this->assertSame('/products/list', $final->newsUrl);
    }

    public function testNullIsAccepted(): void
    {
        $final = $this->create(null);

        $this->assertInstanceOf(NewsCreated::class, $final);
        $this->assertNull($final->newsUrl);
    }

    public function testJavascriptSchemeIsRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            $this->create('javascript:alert(document.cookie)');
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                NewsUrlFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testDataSchemeIsRejected(): void
    {
        $this->expectException(SemanticVariableException::class);

        $this->create('data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==');
    }
}
