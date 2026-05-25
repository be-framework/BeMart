<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberDeleted;
use MyVendor\BeMart\Be\Input\DeleteMemberInput;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeAdminStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MemberDeletedTest extends TestCase
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

    public function testHappyPathSoftDeletesAdmin(): void
    {
        $final = ($this->becoming)(new DeleteMemberInput(loginId: 'shop-owner'));

        $this->assertInstanceOf(MemberDeleted::class, $final);
        $this->assertSame('shop-owner', $final->loginId);
        $this->assertFalse($final->alreadyDeleted);

        // Row stayed in storage (soft delete) but flipped to NON_ACTIVE.
        $persisted = $this->storage->getByLoginId('shop-owner');
        $this->assertNotNull($persisted);
        $this->assertSame(AdminEntity::WORK_INACTIVE, $persisted->work);
        $this->assertSame(0, $persisted->work);
    }

    public function testIdempotentReDeleteIsNoOp(): void
    {
        ($this->becoming)(new DeleteMemberInput(loginId: 'shop-owner'));

        $final = ($this->becoming)(new DeleteMemberInput(loginId: 'shop-owner'));

        $this->assertTrue($final->alreadyDeleted);
    }

    public function testSelfDeleteIsRefused(): void
    {
        // test-admin's adminId matches the session. Self-target → 403.
        $this->expectException(InsufficientAuthorityException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'test-admin'));
    }

    public function testUnknownLoginIdRaisesNotFound(): void
    {
        $this->expectException(AdminNotFoundException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'no-such-admin'));
    }

    public function testNoAdminSessionRefusesBeforeExistenceCheck(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new DeleteMemberInput(loginId: 'no-such-admin'));
    }
}
