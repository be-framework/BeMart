<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Slice 8 production CSRF adapter (BEAR\Csrf\CsrfTokenInterface).
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

        $this->assertTrue($adapter->verify('session-token-abc'));
    }

    public function testReturnsFalseWhenSubmittedTokenDoesNotMatchSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify('different-token'));
    }

    public function testReturnsFalseWhenSubmittedTokenDoesNotMatchGeneratedSessionReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify('any-token'));
    }

    public function testReturnsFalseForEmptyToken(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify(''));
    }

    public function testEmptyStringSessionTreatedAsNoReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = '';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify(''));
        $this->assertFalse($adapter->verify('any-token'));
    }

    public function testNonStringSessionTreatedAsNoReference(): void
    {
        // Defensive: someone misuses the session key with a non-string
        // value. Adapter rejects rather than coercing.
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 12345;

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify('12345'));
    }

    public function testCustomSessionKeyHonored(): void
    {
        // Multi-tenant / non-default deployments can mirror a different key.
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertTrue($adapter->verify('alt-token-value'));
        $this->assertFalse($adapter->verify('session-token-abc'));
    }

    public function testIssueReturnsStoredSessionReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertSame('session-token-abc', $adapter->issue());
    }

    public function testIssueSeedsAReferenceWhenSessionIsEmpty(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $token = $adapter->issue();

        // A reference is generated, stored back into the session, and
        // accepted by the matching verify() call — the form-render ->
        // form-POST round-trip the interface guarantees.
        $this->assertNotSame('', $token);
        $this->assertSame($token, $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
        $this->assertTrue($adapter->verify($token));
    }

    public function testIssueDoesNotRotateAnExistingReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $first = $adapter->issue();
        $second = $adapter->issue();

        // Concurrent form pages in one session must all carry the same
        // valid token — token seeds once, never rotates.
        $this->assertSame($first, $second);
    }

    public function testIssueHonorsACustomSessionKey(): void
    {
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertSame('alt-token-value', $adapter->issue());
    }

    public function testClearRemovesTheStoredReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();
        $token = $adapter->issue();

        $adapter->clear();

        $this->assertArrayNotHasKey(EccubeSharedCsrfTokenAdapter::SESSION_KEY, $_SESSION);
        $this->assertFalse($adapter->verify($token));
    }
}
