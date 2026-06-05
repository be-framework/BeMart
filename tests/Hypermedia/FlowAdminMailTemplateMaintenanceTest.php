<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function array_column;
use function assert;
use function bin2hex;
use function getenv;
use function is_array;
use function putenv;
use function random_bytes;

class FlowAdminMailTemplateMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-mail-template-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-mail-template-csrf-token';

    private static InjectorInterface|null $injector = null;
    private static ExtendedPdoInterface|null $db = null;
    private static ResourceInterface|null $dbResource = null;
    private static string $orderNo;
    private static string $paymentId;
    /** @var array<string, mixed>|null */
    private static array|null $previousSession = null;
    private static string|false $previousCsrfEnv = false;

    public static function setUpBeforeClass(): void
    {
        self::$previousSession = $_SESSION ?? null;
        self::$previousCsrfEnv = getenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);
        $_SESSION = [
            HtmlAdminSessionAdapter::ADMIN_ID_KEY => self::ADMIN_ID,
            EccubeSharedCsrfTokenAdapter::SESSION_KEY => self::CSRF_TOKEN,
        ];
        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=' . self::CSRF_TOKEN);

        self::$injector = Injector::getInstance('html-prod-hal-api-app');
        $mailTemplates = self::$injector->getInstance(MailTemplateStorageInterface::class);
        assert($mailTemplates instanceof MailTemplateStorageInterface);
        if ($mailTemplates->list() === []) {
            $mailTemplates->put(new MailTemplateEntity(
                mailTemplateId: 1,
                mailTemplateName: 'Workflow mail template',
                fileName: 'Mail/workflow.twig',
                subject: 'Workflow mail template subject',
            ));
        }

        $db = self::$injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        self::$db = $db;
        self::$db->beginTransaction();

        $resource = self::$injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        self::$dbResource = $resource;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db instanceof ExtendedPdoInterface && self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        if (self::$previousSession === null) {
            unset($_SESSION);
        } else {
            $_SESSION = self::$previousSession;
        }

        if (self::$previousCsrfEnv === false) {
            putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);
        } else {
            putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=' . self::$previousCsrfEnv);
        }

        self::$db = null;
        self::$dbResource = null;
        self::$injector = null;

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

    #[Alps('goMailTemplateList')]
    public function testMailTemplateList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doUpdateMailTemplate')]
    #[Depends('testMailTemplateList')]
    public function testUpdatesMailTemplate(ResourceObject $response): ResourceObject
    {
        $templates = $this->bodyValue($response, 'mailTemplates');
        $this->assertIsArray($templates);
        $this->assertNotSame([], $templates);

        $template = $templates[0] ?? null;
        $this->assertTrue(is_array($template));

        $updated = $this->resource->post('page://self/admin/mail-template', [
            'mailTemplateId' => $template['mailTemplateId'],
            'mailSubject' => 'Workflow mail template subject',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame('Workflow mail template subject', $this->bodyValue($updated, 'mailSubject'));

        return $updated;
    }

    #[Alps('goOrderMail')]
    #[Depends('testMailTemplateList')]
    public function testOrderMail(ResourceObject $response): ResourceObject
    {
        $suffix = bin2hex(random_bytes(4));
        $payment = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'Workflow Mail Payment ' . $suffix,
            'charge' => 0,
            'ruleMin' => 0,
            'ruleMax' => 999999,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $payment->code);
        $this->assertIsString($payment->body['paymentId'] ?? null);
        self::$paymentId = $payment->body['paymentId'];

        $order = $this->resource->post('page://self/admin/order/create', [
            'customerId' => 'workflow-mail-customer-' . $suffix,
            'paymentMethodId' => (int) self::$paymentId,
            'orderItems' => [
                [
                    'productCode' => 'workflow-mail-' . $suffix,
                    'productName' => 'Workflow Mail Item',
                    'unitPrice' => 1200,
                    'quantity' => 1,
                ],
            ],
            'deliveryFeeTotal' => 0,
            'charge' => 0,
            'discount' => 0,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $order->code);
        $this->assertIsString($order->body['orderNo'] ?? null);
        self::$orderNo = $order->body['orderNo'];

        return $this->follow($response, 'goOrderMail', ['orderNo' => self::$orderNo]);
    }

    #[Alps('goOrderMailConfirm')]
    #[Depends('testOrderMail')]
    public function testOrderMailConfirm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goOrderMailConfirm', ['orderNo' => self::$orderNo]);
    }

    #[Alps('doSendOrderMail')]
    #[Depends('testOrderMailConfirm')]
    public function testSendsOrderMail(ResourceObject $response): ResourceObject
    {
        $sent = $this->resource->post('page://self/admin/order/send-mail', [
            'orderNo' => self::$orderNo,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $sent->code);
        $this->assertSame(self::$orderNo, $this->bodyValue($sent, 'orderNo'));

        return $sent;
    }

    #[Alps('MailHistory')]
    #[Depends('testSendsOrderMail')]
    public function testMailHistory(ResourceObject $response): ResourceObject
    {
        $this->assertIsString($this->bodyValue($response, 'sendDate'));
        $this->assertIsString($this->bodyValue($response, 'mailSubject'));
        $this->assertIsString($this->bodyValue($response, 'mailBody'));

        return $response;
    }

    #[Alps('doDeleteMailTemplate')]
    #[Depends('testUpdatesMailTemplate')]
    public function testDeletesMailTemplate(ResourceObject $response): ResourceObject
    {
        $deleted = $this->resource->delete('page://self/admin/mail-template', [
            'mailTemplateId' => $this->bodyValue($response, 'mailTemplateId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $deleted->code);
        $this->assertSame('doDeleteMailTemplate', $this->bodyValue($deleted, 'transitionId'));

        return $deleted;
    }

    #[Alps('goMailTemplateList')]
    #[Depends('testDeletesMailTemplate')]
    public function testReturnsToMailTemplateList(ResourceObject $response): void
    {
        $list = $this->follow($response, 'goMailTemplateList');
        $templates = $this->bodyValue($list, 'mailTemplates');
        $this->assertIsArray($templates);
        $this->assertNotContains($this->bodyValue($response, 'mailTemplateId'), array_column($templates, 'mailTemplateId'));
    }
}
