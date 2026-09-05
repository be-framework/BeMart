<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Exception\LoginFailedException;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;
use MyVendor\BeMart\Be\Final\CustomerAuthenticated;
use MyVendor\BeMart\Be\Input\LoginInput;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class CustomerAuthenticatedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsAuthenticatedCustomer(): void
    {
        $final = ($this->becoming)(new LoginInput(
            email: 'login-test@example.com',
            password: 'local-dev-member-password',
        ));

        $this->assertInstanceOf(CustomerAuthenticated::class, $final);
        $this->assertSame('10000000aaaa1111bbbb2222cccc3333', $final->customerId);
        $this->assertSame('login-test@example.com', $final->email);
        $this->assertSame('鈴木', $final->name01);
        $this->assertSame(2, $final->customerStatus);
    }

    public function testWrongPasswordRaisesLoginFailed(): void
    {
        $this->expectException(LoginFailedException::class);
        ($this->becoming)(new LoginInput(
            email: 'login-test@example.com',
            password: 'not-the-right-password',
        ));
    }

    public function testUnknownEmailRaisesLoginFailed(): void
    {
        // Same exception as wrong-password case (no user enumeration).
        $this->expectException(LoginFailedException::class);
        ($this->becoming)(new LoginInput(
            email: 'nobody@example.com',
            password: 'local-dev-member-password',
        ));
    }

    public function testWithdrawnCustomerRaisesLoginFailed(): void
    {
        // Withdrawal keeps the row, the password hash, and a
        // deterministic `withdrawn-{customerId}@…` address, so only the
        // status stops the old credentials from minting a session.
        // Same exception as wrong-password (no enumeration).
        $this->expectException(LoginFailedException::class);
        ($this->becoming)(new LoginInput(
            email: 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid',
            password: 'local-dev-member-password',
        ));
    }

    public function testProvisionalCustomerRaisesLoginFailed(): void
    {
        // 仮会員 (customerStatus=1) has not proven the address yet:
        // doActivateCustomer must run first.
        $this->expectException(LoginFailedException::class);
        ($this->becoming)(new LoginInput(
            email: 'provisional@example.com',
            password: 'local-dev-member-password',
        ));
    }

    public function testInvalidEmailFormatRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new LoginInput(
                email: 'not-an-email',
                password: 'local-dev-member-password',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(EmailFormatException::class, $e->getErrors()->exceptions[0]);

            throw $e;
        }
    }

    public function testShortPasswordRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new LoginInput(
                email: 'login-test@example.com',
                password: 'short',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(PasswordFormatException::class, $e->getErrors()->exceptions[0]);

            throw $e;
        }
    }
}
