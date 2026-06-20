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
use function is_array;
use function is_string;
use function random_bytes;

class FlowCustomerRegistrationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-registration';

    private const CSRF_TOKEN = 'workflow-csrf-token';
    private const PASSWORD = 'workflow-password-2026';

    private static string $email;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$email = 'workflow-' . bin2hex(random_bytes(4)) . '@example.com';
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

    #[Alps('Top')]
    public function testIndex(): ResourceObject
    {
        $response = $this->resource->get('page://self/');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('goCustomerRegistration')]
    #[Depends('testIndex')]
    public function testRegistrationForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerRegistration');
    }

    #[Alps('goCustomerRegistrationConfirm')]
    #[Depends('testRegistrationForm')]
    public function testRegistrationConfirm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goCustomerRegistrationConfirm');
    }

    #[Alps('doRegisterCustomer')]
    #[Depends('testRegistrationConfirm')]
    public function testRegistersCustomer(ResourceObject $response): ResourceObject
    {
        $registered = $this->resource->post($this->linkHref($response, 'doRegisterCustomer'), [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'name01' => 'Workflow',
            'name02' => 'Customer',
            'kana01' => 'ワークフロー',
            'kana02' => 'カスタマー',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '千代田1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $registered->code);
        $this->assertSame(self::$email, $this->bodyValue($registered, 'email'));
        $this->assertSame(2, $this->bodyValue($registered, 'customerStatus'));

        return $registered;
    }

    #[Alps('CustomerRegistrationComplete')]
    #[Depends('testRegistersCustomer')]
    public function testRegistrationComplete(ResourceObject $response): ResourceObject
    {
        return $this->followLocation($response);
    }

    /**
     * doActivateCustomer — email-verification (本登録) over the mailed
     * secretKey link.
     *
     * EC-CUBE's mail-auth path registers a provisional (status=1) member
     * carrying a per-customer secretKey; this BeMart build fixes
     * registration to status=2 (mail-auth-OFF), so we recreate the
     * provisional precondition inside the rolled-back session transaction:
     * the just-registered customer (which already carries a server-side
     * 32-char secret_key) is demoted to status=1, then promoted back via
     * the real doActivateCustomer affordance.
     *
     * SSOT: GET #EntryActivate (the mailed landing) advertises
     * #doActivateCustomer; we resolve the POST href via linkHref — never a
     * page:// string literal.
     */
    #[Alps('doActivateCustomer')]
    #[Depends('testRegistersCustomer')]
    public function testActivatesCustomer(): void
    {
        assert(self::$dbSession instanceof WorkflowDbSession);
        $db = self::$dbSession->injector()->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);

        // Resolve the registered customer's server-generated secret_key and
        // demote them to provisional (status=1) — the mail-auth precondition.
        $row = $db->fetchOne(
            'SELECT id, secret_key FROM dtb_customer WHERE email = :email',
            ['email' => self::$email],
        );
        assert(is_array($row) && is_string($row['secret_key']) && $row['secret_key'] !== '');
        $secretKey = $row['secret_key'];

        $db->perform(
            'UPDATE dtb_customer SET customer_status_id = 1 WHERE id = :id',
            ['id' => $row['id']],
        );

        // GET the mailed landing screen (#EntryActivate), then POST the
        // advertised #doActivateCustomer affordance with the secretKey.
        $landing = $this->resource->get('page://self/entry/activate', ['secretKey' => $secretKey]);
        $this->assertSame(Code::OK, $landing->code);

        $activated = $this->resource->post($this->linkHref($landing, 'doActivateCustomer'), [
            'secretKey' => $secretKey,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $activated->code);
        $this->assertSame(2, $this->bodyValue($activated, 'customerStatus'));
        $this->assertSame(self::$email, $this->bodyValue($activated, 'email'));

        // The customer is now active in storage.
        $status = $db->fetchValue(
            'SELECT customer_status_id FROM dtb_customer WHERE id = :id',
            ['id' => $row['id']],
        );
        $this->assertSame(2, (int) $status);
    }

    #[Alps('doLogin')]
    #[Depends('testRegistrationComplete')]
    public function testLogsIn(ResourceObject $response): ResourceObject
    {
        $top = $this->follow($response, 'goTop');
        $loginForm = $this->follow($top, 'goLogin');
        $loggedIn = $this->resource->post($this->linkHref($loginForm, 'doLogin'), [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $loggedIn->code);
        $this->assertSame(self::$email, $this->bodyValue($loggedIn, 'email'));

        assert(self::$dbSession instanceof WorkflowDbSession);
        self::$dbSession->session()->setCustomerId((string) $this->bodyValue($loggedIn, 'customerId'));

        return $loggedIn;
    }

    #[Alps('Mypage')]
    #[Depends('testLogsIn')]
    public function testMypage(ResourceObject $response): void
    {
        $mypage = $this->follow($response, 'goMypage');

        $this->assertSame(self::$email, $this->bodyValue($mypage, 'email'));
    }
}
