<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberCreated;
use MyVendor\BeMart\Be\Input\CreateMemberInput;
use MyVendor\BeMart\Be\Reason\Query\FakeAdminStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MemberCreatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private FakeAdminStorage $storage;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
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
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->storage = $injector->getInstance(FakeAdminStorage::class);
    }

    public function testHappyPathCreatesActiveAdmin(): void
    {
        $final = ($this->becoming)(new CreateMemberInput(
            loginId: 'new-admin',
            password: 'new-admin-password-2026',
            name: '新規管理者',
            authority: 1,
        ));

        $this->assertInstanceOf(MemberCreated::class, $final);
        $this->assertSame('new-admin', $final->loginId);
        $this->assertSame('新規管理者', $final->name);
        $this->assertSame(1, $final->authority);
        // Newly-created admins are immediately active.
        $this->assertSame(1, $final->work);
        $this->assertMatchesRegularExpression('/\Aad[0-9a-f]{30}\z/', $final->adminId);

        // Persisted and looks the same.
        $persisted = $this->storage->getByLoginId('new-admin');
        $this->assertNotNull($persisted);
        $this->assertSame('新規管理者', $persisted->name);
        $this->assertNotSame('new-admin-password-2026', $persisted->passwordHash);
    }

    public function testDuplicateLoginIdIsRejected(): void
    {
        $this->expectException(LoginIdAlreadyTakenException::class);
        ($this->becoming)(new CreateMemberInput(
            loginId: 'test-admin',  // already present in fixture
            password: 'duplicate-attempt-2026',
            name: '別人',
            authority: 1,
        ));
    }

    public function testInvalidAuthorityRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new CreateMemberInput(
            loginId: 'odd-admin',
            password: 'odd-password-2026',
            name: '奇権限',
            authority: 99,
        ));
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new CreateMemberInput(
            loginId: 'attacker',
            password: 'attacker-password-2026',
            name: '侵入者',
            authority: 0,
        ));
    }
}
