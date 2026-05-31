<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\InvalidCurrentPasswordException;
use MyVendor\BeMart\Be\Exception\PasswordConfirmationMismatchException;
use MyVendor\BeMart\Be\Exception\PasswordPolicyViolationException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPasswordChanged;
use MyVendor\BeMart\Be\Input\ChangeAdminPasswordInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminPasswordChangedTest extends TestCase
{
    /** test-admin (authority=0) — its bcrypt hash verifies this plaintext. */
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const CURRENT_PASSWORD = 'admin-test-password-2026';

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

    public function testHappyPathChangesPassword(): void
    {
        $final = ($this->becoming)(new ChangeAdminPasswordInput(
            currentPassword: self::CURRENT_PASSWORD,
            changePasswordFirst: 'new-strong-password-2026',
            changePasswordSecond: 'new-strong-password-2026',
        ));

        $this->assertInstanceOf(AdminPasswordChanged::class, $final);
        $this->assertSame(self::TEST_ADMIN_ID, $final->adminId);
        $this->assertSame('test-admin', $final->loginId);
    }

    public function testWrongCurrentPasswordRejected(): void
    {
        $this->expectException(InvalidCurrentPasswordException::class);
        ($this->becoming)(new ChangeAdminPasswordInput(
            currentPassword: 'not-the-current-password',
            changePasswordFirst: 'new-strong-password-2026',
            changePasswordSecond: 'new-strong-password-2026',
        ));
    }

    public function testConfirmationMismatchRejected(): void
    {
        $this->expectException(PasswordConfirmationMismatchException::class);
        ($this->becoming)(new ChangeAdminPasswordInput(
            currentPassword: self::CURRENT_PASSWORD,
            changePasswordFirst: 'new-strong-password-2026',
            changePasswordSecond: 'different-confirmation-2026',
        ));
    }

    public function testTooShortPasswordRejected(): void
    {
        $this->expectException(PasswordPolicyViolationException::class);
        ($this->becoming)(new ChangeAdminPasswordInput(
            currentPassword: self::CURRENT_PASSWORD,
            changePasswordFirst: 'short',
            changePasswordSecond: 'short',
        ));
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ChangeAdminPasswordInput(
            currentPassword: self::CURRENT_PASSWORD,
            changePasswordFirst: 'new-strong-password-2026',
            changePasswordSecond: 'new-strong-password-2026',
        ));
    }
}
