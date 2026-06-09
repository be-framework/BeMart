<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Provider\CustomerIdProvider;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowTestSession;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function assert;
use function bin2hex;
use function random_bytes;

class FlowCustomerRegistrationTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-registration';

    private const CSRF_TOKEN = 'workflow-csrf-token';
    private const PASSWORD = 'workflow-password-2026';

    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $email;
    private static string $activationEmail;
    private static string $activationSecretKey;
    private static WorkflowTestSession|null $session = null;

    public static function setUpBeforeClass(): void
    {
        self::$email = 'workflow-' . bin2hex(random_bytes(4)) . '@example.com';
        self::$session = WorkflowTestSession::fromCurrent();
        self::$session->setCsrfToken(self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;

        $customerCommand = self::$injector->getInstance(CustomerCommandInterface::class);
        assert($customerCommand instanceof CustomerCommandInterface);
        $customerIds = self::$injector->getInstance(CustomerIdProvider::class);
        assert($customerIds instanceof CustomerIdProvider);
        $passwordHasher = self::$injector->getInstance(PasswordHasherInterface::class);
        assert($passwordHasher instanceof PasswordHasherInterface);

        self::$activationEmail = 'workflow-activation-' . bin2hex(random_bytes(4)) . '@example.com';
        self::$activationSecretKey = 'workflow-activation-' . bin2hex(random_bytes(8));
        $customerCommand->register(new CustomerEntity(
            customerId: $customerIds->get(),
            email: self::$activationEmail,
            passwordHash: $passwordHasher->hash(self::PASSWORD),
            name01: 'Workflow',
            name02: 'Activation',
            kana01: 'ワークフロー',
            kana02: 'アクティベーション',
            companyName: null,
            phoneNumber: '0312345678',
            postalCode: '1000001',
            pref: 13,
            addr01: '千代田区',
            addr02: '千代田1-1',
            birth: null,
            sex: null,
            job: null,
            initialPoint: 0,
            customerStatus: 1,
            secretKey: self::$activationSecretKey,
        ));

        self::$db->beginTransaction();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        self::$session?->restore();

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;
        self::$session = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        if (self::$dbResource instanceof ResourceInterface) {
            return self::$dbResource;
        }

        assert(self::$injector instanceof InjectorInterface);
        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;

        return $resource;
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
        $registered = $this->resource->post('page://self/entry', [
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

    #[Alps('doActivateCustomer')]
    #[Depends('testRegistrationComplete')]
    public function testActivatesCustomer(ResourceObject $response): ResourceObject
    {
        $activated = $this->resource->post('page://self/entry/activate', [
            'secretKey' => self::$activationSecretKey,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $activated->code);
        $this->assertSame(self::$activationEmail, $this->bodyValue($activated, 'email'));
        $this->assertSame(2, $this->bodyValue($activated, 'customerStatus'));

        return $activated;
    }

    #[Alps('doLogin')]
    #[Depends('testRegistrationComplete')]
    public function testLogsIn(ResourceObject $response): ResourceObject
    {
        $loggedIn = $this->resource->post('page://self/login', [
            'email' => self::$email,
            'password' => self::PASSWORD,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $loggedIn->code);
        $this->assertSame(self::$email, $this->bodyValue($loggedIn, 'email'));

        assert(self::$session instanceof WorkflowTestSession);
        self::$session->setCustomerId((string) $this->bodyValue($loggedIn, 'customerId'));

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
