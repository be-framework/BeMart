<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\CustomerAlreadyActivatedException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ActivationMailResent;
use MyVendor\BeMart\Be\Input\ResendActivationMailInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 ALPS-audit remediation — the admin Customer transition
 * doResendActivationMail (domain layer).
 *
 * Mirrors AdminCustomerDeletedTest's build pattern: a fresh injector per
 * admin-session shape, the seed `provisional@example.com` (customerStatus
 * = 1, carries a secretKey) as the happy-path 仮会員 target.
 */
final class ActivationMailResentTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const PROVISIONAL_EMAIL = 'provisional@example.com';
    private const PROVISIONAL_ID = '20000000dddd2222eeee3333ffff4444';
    private const PROVISIONAL_SECRET_KEY = 'pending-secret-key-pilot7-2026abcd';
    private const ACTIVE_EMAIL = 'alice@example.com';

    private BecomingInterface $becoming;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh injector with the given admin session adminId
     * (null = admin-anonymous). Same override pattern as
     * AdminCustomerDeletedTest.
     */
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
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
    }

    public function testProvisionalCustomerReceivesActivationMail(): void
    {
        $final = ($this->becoming)(new ResendActivationMailInput(
            email: self::PROVISIONAL_EMAIL,
        ));

        $this->assertInstanceOf(ActivationMailResent::class, $final);
        $this->assertSame(self::PROVISIONAL_ID, $final->customerId);
        $this->assertSame(self::PROVISIONAL_EMAIL, $final->email);

        $activations = $this->mailer->customerActivations;
        $this->assertCount(1, $activations);
        $this->assertSame(self::PROVISIONAL_EMAIL, $activations[0]['email']);
        $this->assertSame(self::PROVISIONAL_SECRET_KEY, $activations[0]['secretKey']);
    }

    public function testUnsafeReplaySendsAFreshMailEachTime(): void
    {
        // ALPS marks the transition `unsafe` — each call fires a new mail.
        ($this->becoming)(new ResendActivationMailInput(self::PROVISIONAL_EMAIL));
        ($this->becoming)(new ResendActivationMailInput(self::PROVISIONAL_EMAIL));

        $this->assertCount(2, $this->mailer->customerActivations);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ResendActivationMailInput(self::PROVISIONAL_EMAIL));
    }

    public function testNoAdminSessionRefusesBeforeExistenceCheck(): void
    {
        // Anti-enumeration: admin-anonymous + unknown email ⇒ 403 not 404.
        // The AUTHZ guard must run BEFORE the existence probe.
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ResendActivationMailInput('nobody@example.com'));
    }

    public function testUnknownEmailRaisesCustomerNotFound(): void
    {
        $this->expectException(CustomerNotFoundException::class);
        ($this->becoming)(new ResendActivationMailInput('nobody@example.com'));
    }

    public function testAlreadyActiveCustomerRaisesAlreadyActivated(): void
    {
        // alice@example.com is an active member (customerStatus = 2, no
        // secretKey) — resending an activation mail is meaningless.
        $this->expectException(CustomerAlreadyActivatedException::class);
        ($this->becoming)(new ResendActivationMailInput(self::ACTIVE_EMAIL));
    }

    public function testAlreadyActiveCustomerSendsNoMail(): void
    {
        try {
            ($this->becoming)(new ResendActivationMailInput(self::ACTIVE_EMAIL));
        } catch (CustomerAlreadyActivatedException) {
            // expected — assertion is on the side effect below.
        }

        $this->assertCount(0, $this->mailer->customerActivations);
    }
}
