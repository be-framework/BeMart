<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Slice 8 production CSRF adapter.
 *
 * The adapter has two resolution paths:
 *   1. $_SESSION[_csrf_token] matches submitted token (HTTP context, or
 *      test fixture)
 *   2. Otherwise reject (false)
 */
final class EccubeSharedCsrfTokenAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }

    public function testReturnsTrueWhenSubmittedTokenMatchesSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertTrue($adapter->isValid('session-token-abc'));
    }

    public function testReturnsFalseWhenSubmittedTokenDoesNotMatchSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('different-token'));
    }

    public function testReturnsFalseWhenSubmittedTokenDoesNotMatchGeneratedSessionReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('any-token'));
    }

    public function testReturnsFalseForNullToken(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid(null));
    }

    public function testReturnsFalseForEmptyToken(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid(''));
    }

    public function testEmptyStringSessionTreatedAsNoReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = '';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid(''));
        $this->assertFalse($adapter->isValid('any-token'));
    }

    public function testNonStringSessionTreatedAsNoReference(): void
    {
        // Defensive: someone misuses the session key with a non-string
        // value. Adapter rejects rather than coercing.
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 12345;

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('12345'));
    }

    public function testCustomSessionKeyHonored(): void
    {
        // Multi-tenant / non-default deployments can mirror a different key.
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertTrue($adapter->isValid('alt-token-value'));
        $this->assertFalse($adapter->isValid('session-token-abc'));
    }

    public function testTokenReturnsStoredSessionReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertSame('session-token-abc', $adapter->token);
    }

    public function testTokenSeedsAReferenceWhenSessionIsEmpty(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $token = $adapter->token;

        // A reference is generated, stored back into the session, and
        // accepted by the matching isValid() call — the form-render ->
        // form-POST round-trip the interface guarantees.
        $this->assertNotSame('', $token);
        $this->assertSame($token, $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
        $this->assertTrue($adapter->isValid($token));
    }

    public function testTokenDoesNotRotateAnExistingReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $first = $adapter->token;
        $second = $adapter->token;

        // Concurrent form pages in one session must all carry the same
        // valid token — token seeds once, never rotates.
        $this->assertSame($first, $second);
    }

    public function testTokenHonorsACustomSessionKey(): void
    {
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertSame('alt-token-value', $adapter->token);
    }
}
