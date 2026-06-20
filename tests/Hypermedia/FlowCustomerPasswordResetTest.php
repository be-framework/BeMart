<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Dev\Http\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function is_string;
use function random_bytes;

/**
 * doRequestPasswordReset + doResetPassword — customer password-reset flow.
 *
 * Models tests/Hypermedia/FlowCustomerRegistrationTest: register a fresh
 * customer (so the email maps to a real account), then drive the
 * password-reset round trip entirely through hypermedia transitions:
 *
 *   Top → goLogin → goForgotPassword (ForgotPassword state)
 *       → doRequestPasswordReset(email)  [reset_key persisted + mail sent]
 *       → GET /reset?resetKey=…          (PasswordReset state)
 *       → doResetPassword(resetKey, password)  [token consumed]
 *
 * The reset key never leaves the server in the response body (anti-
 * enumeration), so the consumer step reads it back from the row the
 * request step wrote: dtb_customer.reset_key, via the shared
 * ExtendedPdoInterface connection (same one the Resource SQL runs on,
 * class-level transaction rolled back in tearDown).
 */
class FlowCustomerPasswordResetTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-password-reset';

    private const CSRF_TOKEN = 'workflow-csrf-token';
    private const PASSWORD = 'workflow-password-2026';
    private const NEW_PASSWORD = 'workflow-new-password-2026';

    private static string $email;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$email = 'reset-' . bin2hex(random_bytes(4)) . '@example.com';
        self::$dbSession = WorkflowDbSession::startWithCsrfToken(self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return self::$dbSession->resource();
    }

    #[Alps('goCustomerRegistration')]
    public function testRegistersCustomer(): ResourceObject
    {
        $top = $this->resource->get('page://self/');
        $this->assertSame(Code::OK, $top->code);

        $entry = $this->follow($top, 'goCustomerRegistration');
        $confirm = $this->follow($entry, 'goCustomerRegistrationConfirm');

        $registered = $this->resource->post($this->linkHref($confirm, 'doRegisterCustomer'), [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'name01' => 'Workflow',
            'name02' => 'Reset',
            'kana01' => 'ワークフロー',
            'kana02' => 'リセット',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '千代田1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $registered->code);
        $this->assertSame(self::$email, $this->bodyValue($registered, 'email'));

        return $registered;
    }

    #[Alps('goForgotPassword')]
    #[Depends('testRegistersCustomer')]
    public function testForgotPasswordForm(): ResourceObject
    {
        $top = $this->resource->get('page://self/');
        $login = $this->follow($top, 'goLogin');

        // SSOT gap closed: the Login state now advertises goForgotPassword.
        $forgot = $this->follow($login, 'goForgotPassword');

        $this->assertSame(Code::OK, $forgot->code);

        return $forgot;
    }

    #[Alps('doRequestPasswordReset')]
    #[Depends('testForgotPasswordForm')]
    public function testRequestsPasswordReset(ResourceObject $response): ResourceObject
    {
        $requested = $this->resource->post($this->linkHref($response, 'doRequestPasswordReset'), [
            'email' => self::$email,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        // Anti-enumeration: uniform 200 regardless of email existence.
        $this->assertSame(Code::OK, $requested->code);

        return $requested;
    }

    #[Alps('doResetPassword')]
    #[Depends('testRequestsPasswordReset')]
    public function testResetsPassword(): void
    {
        // The reset key is never returned in a response body. Read it back
        // from the row the request step wrote (dtb_customer.reset_key),
        // over the same connection the Resource SQL used.
        $resetKey = $this->resetKeyFor(self::$email);
        $this->assertIsString($resetKey);
        $this->assertNotSame('', $resetKey);

        // PasswordReset state — direct GET entrypoint with the emailed key
        // (GET entrypoints are permitted; only unsafe page://self calls are not).
        $resetForm = $this->resource->get('page://self/reset', ['resetKey' => $resetKey]);
        $this->assertSame(Code::OK, $resetForm->code);
        $this->assertSame($resetKey, $this->bodyValue($resetForm, 'resetKey'));

        $reset = $this->resource->post($this->linkHref($resetForm, 'doResetPassword'), [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $reset->code);
    }

    private function resetKeyFor(string $email): string|null
    {
        assert(self::$dbSession instanceof WorkflowDbSession);
        $db = self::$dbSession->injector()->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);

        $resetKey = $db->fetchValue(
            'SELECT reset_key FROM dtb_customer WHERE email = :email LIMIT 1',
            ['email' => $email],
        );

        return is_string($resetKey) ? $resetKey : null;
    }
}
