<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;
use MyVendor\BeMart\Be\Final\AdminAuthenticated;
use MyVendor\BeMart\Be\Input\AdminLoginInput;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class AdminAuthenticatedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsAuthenticatedAdmin(): void
    {
        $final = ($this->becoming)(new AdminLoginInput(
            loginId: 'test-admin',
            password: 'admin-test-password-2026',
        ));

        $this->assertInstanceOf(AdminAuthenticated::class, $final);
        $this->assertSame('ad000000000000000000000000000001', $final->adminId);
        $this->assertSame('test-admin', $final->loginId);
        $this->assertSame('テスト管理者', $final->name);
        $this->assertSame(0, $final->authority);
    }

    public function testWrongPasswordRaisesAdminLoginFailed(): void
    {
        $this->expectException(AdminLoginFailedException::class);
        ($this->becoming)(new AdminLoginInput(
            loginId: 'test-admin',
            password: 'not-the-right-password',
        ));
    }

    public function testUnknownLoginIdRaisesAdminLoginFailed(): void
    {
        // Same exception as wrong-password case — no admin enumeration.
        $this->expectException(AdminLoginFailedException::class);
        ($this->becoming)(new AdminLoginInput(
            loginId: 'no-such-admin',
            password: 'admin-test-password-2026',
        ));
    }

    public function testShortPasswordRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new AdminLoginInput(
                loginId: 'test-admin',
                password: 'short',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(PasswordFormatException::class, $e->getErrors()->exceptions[0]);

            throw $e;
        }
    }
}
