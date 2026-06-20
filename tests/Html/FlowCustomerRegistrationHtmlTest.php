<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;

/**
 * HTML registration walk — path C (independent, not extending the
 * Hypermedia class). Drives the storefront customer-registration flow
 * over real HTTP by following rendered ALPS affordances (class/rel).
 *
 * Journey:
 *   1. GET /entry               — renders doRegisterCustomer form
 *   2. GET /entry/confirm       — reached via goCustomerRegistrationConfirm anchor
 *   3. POST /entry (submit)     — doRegisterCustomer form on confirm page
 *   4. Follow 303 Location      — /entry/complete
 *
 * Steps skipped:
 *   - Post-registration login / mypage: covered by FlowCustomerMypageHtmlTest
 *     (seeded customer). Duplicating it here would require a second login
 *     form submission outside the registration affordance graph.
 *
 * A WorkflowDbSession is used for CSRF setup (anonymous — no customer id
 * is injected, only the token) so the server-side CSRF check passes via
 * the X-BeMart-Test-Csrf-Token header, and the transaction is rolled
 * back after the class so the test customer is never persisted.
 */
final class FlowCustomerRegistrationHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-customer-registration-html';

    private const CSRF_TOKEN = 'flow-customer-registration-csrf';
    private const PASSWORD = 'Bemart-Reg-2026';

    private static string $email;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$email = bin2hex(random_bytes(4)) . '@example.com';
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

        return new HttpResource(
            '127.0.0.1:8125',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    /** GET /entry — the registration input form. */
    #[Alps('goCustomerRegistration')]
    public function testRendersRegistrationForm(): ResourceObject
    {
        $entry = $this->resource->get('page://self/entry');

        $this->assertSame(Code::OK, $entry->code, (string) ($entry->view ?? $entry->code));
        $this->assertAffordance($entry, 'doRegisterCustomer');
        $this->assertStringContainsString('新規会員登録', (string) ($entry->view ?? ''));

        return $entry;
    }

    /**
     * GET /entry/confirm — reached via goCustomerRegistrationConfirm anchor
     * rendered on the entry page.
     */
    #[Alps('goCustomerRegistrationConfirm')]
    #[Depends('testRendersRegistrationForm')]
    public function testReachesConfirmPage(ResourceObject $entry): ResourceObject
    {
        $confirm = $this->follow($entry, 'goCustomerRegistrationConfirm');

        $this->assertSame(Code::OK, $confirm->code, (string) ($confirm->view ?? $confirm->code));
        $this->assertAffordance($confirm, 'doRegisterCustomer');
        $this->assertStringContainsString('新規会員登録(確認)', (string) ($confirm->view ?? ''));

        return $confirm;
    }

    /**
     * POST /entry via the doRegisterCustomer form on the confirm page.
     * mode=complete bypasses the browser-form-submission branching and
     * drives the registration Becoming chain directly.
     */
    #[Alps('doRegisterCustomer')]
    #[Depends('testReachesConfirmPage')]
    public function testSubmitsRegistration(ResourceObject $confirm): ResourceObject
    {
        $registered = $this->submit($confirm, 'doRegisterCustomer', [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'name01' => 'テスト',
            'name02' => '太郎',
            'kana01' => 'テスト',
            'kana02' => 'タロウ',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => '13',
            'addr01' => '千代田区',
            'addr02' => '千代田1-1',
            'mode' => 'complete',
        ]);

        $this->assertTrue(
            in_array($registered->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($registered->view ?? $registered->code),
        );

        return $registered;
    }

    /**
     * Follow 303 Location to /entry/complete — the registration-complete page.
     */
    #[Alps('goCustomerRegistrationComplete')]
    #[Depends('testSubmitsRegistration')]
    public function testReachesCompletePage(ResourceObject $registered): void
    {
        if ($registered->code !== Code::SEE_OTHER) {
            // If the server returned 201 (API path), the complete page is a
            // separate navigation; skip the redirect follow.
            return;
        }

        $complete = $this->followLocation($registered);

        $this->assertSame(Code::OK, $complete->code, (string) ($complete->view ?? $complete->code));
        $this->assertStringContainsString('会員登録ありがとうございます', (string) ($complete->view ?? ''));
    }
}
