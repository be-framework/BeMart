<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberUpdated;
use MyVendor\BeMart\Be\Input\UpdateMemberInput;
use MyVendor\BeMart\Be\Reason\Query\FakeAdminStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MemberUpdatedTest extends TestCase
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

    public function testHappyPathUpdatesNameAndMail(): void
    {
        $final = ($this->becoming)(new UpdateMemberInput(
            loginId: 'shop-owner',
            name: '改名後オーナー',
            mailAddress: 'new-owner@example.com',
        ));

        $this->assertInstanceOf(MemberUpdated::class, $final);
        $this->assertSame('shop-owner', $final->loginId);
        $this->assertSame('改名後オーナー', $final->name);
        $this->assertSame('new-owner@example.com', $final->mailAddress);
        // Authority and work are preserved (no role-flip via this path).
        $this->assertSame(1, $final->authority);
        $this->assertSame(1, $final->work);

        // Persisted reflects the merged shape.
        $persisted = $this->storage->getByLoginId('shop-owner');
        $this->assertNotNull($persisted);
        $this->assertSame('改名後オーナー', $persisted->name);
        $this->assertSame('new-owner@example.com', $persisted->mailAddress);
    }

    public function testPartialUpdateLeavesUnchangedFieldsAlone(): void
    {
        $final = ($this->becoming)(new UpdateMemberInput(
            loginId: 'deputy',
            name: '副管理者（改）',
            // mailAddress omitted — keep existing.
        ));

        $this->assertSame('副管理者（改）', $final->name);
        $this->assertSame('deputy@example.com', $final->mailAddress);
    }

    public function testUnknownLoginIdRaisesNotFound(): void
    {
        $this->expectException(AdminNotFoundException::class);
        ($this->becoming)(new UpdateMemberInput(
            loginId: 'no-such-admin',
            name: 'irrelevant',
        ));
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateMemberInput(
            loginId: 'shop-owner',
            name: 'attacker-rename',
        ));
    }
}
