<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use MyVendor\BeMart\Resource\Page\Admin\ChangePassword;
use MyVendor\BeMart\Resource\Page\Admin\CreateCustomer;
use MyVendor\BeMart\Resource\Page\Admin\Login as AdminLogin;
use MyVendor\BeMart\Resource\Page\Admin\Member;
use MyVendor\BeMart\Resource\Page\Admin\TwoFactorAuth;
use MyVendor\BeMart\Resource\Page\Admin\TwoFactorAuthSet;
use MyVendor\BeMart\Resource\Page\Entry;
use MyVendor\BeMart\Resource\Page\Entry\Activate;
use MyVendor\BeMart\Resource\Page\Login;
use MyVendor\BeMart\Resource\Page\Reset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SensitiveParameter;

/**
 * Boundary contract: every Resource-layer onPost/onPut parameter carrying a domain
 * credential (password/secret/token-shaped) must be marked #[SensitiveParameter], so
 * that the onPost/onPut method's own frame is redacted in a stack trace when an
 * exception propagates through it while the method is still on the call stack (e.g. a
 * SemanticVariableException re-thrown for a non-browser-form request, or a later throw
 * during $this->becoming(...)). This protects only that one frame, not the whole trace
 * or any copy of the same value held elsewhere (e.g. BEAR\Resource's own request
 * object) — #[CsrfProtected] specifically never throws (403 body + return), so it is
 * not part of this protection's rationale.
 *
 * This list is the concrete inventory found by manual audit; a newly added
 * credential-bearing onPost/onPut method must be added here as well as annotated at
 * the source, or this test silently stops proving anything for it.
 *
 * Regression motivation: Entry::onPost's $password/$password_confirm were missed by
 * a manual grep-based audit pass until this map forced an explicit enumeration.
 */
final class CredentialParameterSensitiveParameterTest extends TestCase
{
    /** @return iterable<string, array{class-string, string, list<string>}> */
    public static function credentialParameterProvider(): iterable
    {
        yield 'Login::onPost' => [Login::class, 'onPost', ['password']];
        yield 'Admin\Login::onPost' => [AdminLogin::class, 'onPost', ['password']];
        yield 'Reset::onPost' => [Reset::class, 'onPost', ['resetKey', 'password']];
        yield 'Entry::onPost' => [Entry::class, 'onPost', ['password', 'password_confirm']];
        yield 'Entry\Activate::onPost' => [Activate::class, 'onPost', ['secretKey']];
        yield 'Admin\ChangePassword::onPost' => [
            ChangePassword::class,
            'onPost',
            ['currentPassword', 'changePasswordFirst', 'changePasswordSecond'],
        ];
        yield 'Admin\CreateCustomer::onPost' => [CreateCustomer::class, 'onPost', ['password']];
        yield 'Admin\Member::onPost' => [Member::class, 'onPost', ['password', 'passwordConfirm']];
        yield 'Admin\TwoFactorAuth::onPost' => [TwoFactorAuth::class, 'onPost', ['deviceToken']];
        yield 'Admin\TwoFactorAuthSet::onPut' => [TwoFactorAuthSet::class, 'onPut', ['deviceToken', 'authKey']];
    }

    /** @param class-string $class @param list<string> $credentialParams */
    #[DataProvider('credentialParameterProvider')]
    public function testCredentialParametersAreMarkedSensitive(string $class, string $method, array $credentialParams): void
    {
        $reflection = new ReflectionMethod($class, $method);
        $byName = [];
        foreach ($reflection->getParameters() as $parameter) {
            $byName[$parameter->getName()] = $parameter;
        }

        foreach ($credentialParams as $name) {
            $this->assertArrayHasKey($name, $byName, "{$class}::{$method}(\${$name}) must exist");
            $attributes = $byName[$name]->getAttributes(SensitiveParameter::class);
            $this->assertNotEmpty(
                $attributes,
                "{$class}::{$method}(\${$name}) must be #[SensitiveParameter] so this method's own "
                . 'stack frame redacts it when an exception propagates through the frame.',
            );
        }
    }
}
