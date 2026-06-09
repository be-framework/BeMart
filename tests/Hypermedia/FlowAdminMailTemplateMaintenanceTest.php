<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use BEAR\Dev\Http\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowFixtureBoundary;
use PHPUnit\Framework\Attributes\Depends;
use Ray\Di\InjectorInterface;

use function array_column;
use function assert;
use function bin2hex;
use function is_array;
use function random_bytes;

class FlowAdminMailTemplateMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-mail-template-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-mail-template-csrf-token';

    private static string $orderNo;
    private static string $paymentId;
    private static WorkflowDbSession|null $dbSession = null;
    private static WorkflowFixtureBoundary|null $fixtures = null;

    public static function setUpBeforeClass(): void
    {
        self::$dbSession = WorkflowDbSession::startForAdmin(
            self::ADMIN_ID,
            self::CSRF_TOKEN,
            static function (InjectorInterface $injector): void {
                self::$fixtures = WorkflowFixtureBoundary::fromInjector($injector);
                self::$fixtures->ensureMailTemplateListVisible(new MailTemplateEntity(
                    mailTemplateId: 1,
                    mailTemplateName: 'Workflow mail template',
                    fileName: 'Mail/workflow.twig',
                    subject: 'Workflow mail template subject',
                ));
            },
        );
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore(static function (): void {
            self::$fixtures?->cleanup();
        });
        self::$dbSession = null;
        self::$fixtures = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return self::$dbSession->resource();
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
        self::$paymentId = $this->bodyString($payment, 'paymentId');

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
        self::$orderNo = $this->bodyString($order, 'orderNo');

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
