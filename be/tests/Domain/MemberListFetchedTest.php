<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberListFetched;
use MyVendor\BeMart\Be\Input\GetMemberListInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

final class MemberListFetchedTest extends TestCase
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
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathListsAllAdmins(): void
    {
        $final = ($this->becoming)(new GetMemberListInput());

        $this->assertInstanceOf(MemberListFetched::class, $final);
        $this->assertGreaterThanOrEqual(3, $final->count);
        $loginIds = array_column($final->members, 'loginId');
        $this->assertContains('test-admin', $loginIds);
        $this->assertContains('shop-owner', $loginIds);
        $this->assertContains('deputy', $loginIds);
    }

    public function testProjectionDoesNotLeakPasswordHash(): void
    {
        $final = ($this->becoming)(new GetMemberListInput());

        foreach ($final->members as $row) {
            $this->assertArrayNotHasKey('passwordHash', $row);
        }
    }

    public function testNameKeywordFilterNarrowsResults(): void
    {
        $final = ($this->becoming)(new GetMemberListInput(nameKeyword: 'テスト'));

        $this->assertSame(1, $final->count);
        $this->assertSame('test-admin', $final->members[0]['loginId']);
        $this->assertSame('テスト', $final->filters['nameKeyword']);
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetMemberListInput());
    }
}
