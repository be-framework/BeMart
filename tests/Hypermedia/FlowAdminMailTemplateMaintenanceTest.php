<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Dev\Http\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function array_column;
use function assert;
use function bin2hex;
use function random_bytes;

class FlowAdminMailTemplateMaintenanceTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-mail-template-maintenance';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-mail-template-csrf-token';
    private const SESSION_PREFIX = 'workflow-mail-template-session';

    private static string $orderNo;
    private static string $email;
    private static string $mailTemplateName;
    private static string $productCode;
    private static string $productName;
    private static string $updatedProductName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$email = 'workflow-mail-' . $suffix . '@example.com';
        self::$mailTemplateName = 'Workflow mail template ' . $suffix;
        self::$productCode = 'workflow-mail-product-' . $suffix;
        self::$productName = 'Workflow Mail Product ' . $suffix;
        self::$updatedProductName = 'Workflow Mail Published ' . $suffix;
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
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

    #[Alps('goMailTemplateList')]
    public function testMailTemplateList(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doCreateMailTemplate')]
    #[Depends('testMailTemplateList')]
    public function testCreatesMailTemplate(ResourceObject $response): ResourceObject
    {
        $created = $this->resource->post($this->linkHref($response, 'doCreateMailTemplate'), [
            'mailTemplateName' => self::$mailTemplateName,
            'fileName' => 'Mail/workflow-' . bin2hex(random_bytes(4)) . '.twig',
            'mailSubject' => 'Workflow mail template subject',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $created->code);
        $this->assertSame(self::$mailTemplateName, $this->bodyValue($created, 'mailTemplateName'));

        return $created;
    }

    #[Alps('doUpdateMailTemplate')]
    #[Depends('testCreatesMailTemplate')]
    public function testUpdatesMailTemplate(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post($this->linkHref($response, 'doUpdateMailTemplate'), [
            'mailTemplateId' => $this->bodyValue($response, 'mailTemplateId'),
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
        $paymentList = $this->follow($response, 'goPaymentList');
        $payment = $this->resource->post($this->linkHref($paymentList, 'doCreatePayment'), [
            'paymentMethodName' => 'Workflow Mail Payment ' . self::$productCode,
            'charge' => 0,
            'ruleMin' => 0,
            'ruleMax' => 999999,
            'visible' => true,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $payment->code);

        $paymentList = $this->follow($payment, 'goPaymentList');
        $productList = $this->follow($paymentList, 'goProductList', ['nameKeyword' => self::$productName]);
        $createdProduct = $this->resource->post($this->linkHref($productList, 'doCreateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => 1200,
            'stock' => 5,
            'productStatus' => 1,
            'description' => 'Created by flow-admin-mail-template-maintenance for order mail verification.',
            'searchWord' => 'workflow mail template product',
            'note' => 'Created through admin hypermedia before storefront checkout.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $createdProduct->code);

        $adminProduct = $this->followLocation($createdProduct);
        $publishedProduct = $this->resource->put($this->linkHref($adminProduct, 'doUpdateProduct'), [
            'productCode' => self::$productCode,
            'productName' => self::$updatedProductName,
            'price02' => 1200,
            'stock' => 5,
            'productStatus' => 1,
            'description' => 'Published by flow-admin-mail-template-maintenance.',
            'searchWord' => 'workflow mail template published',
            'note' => 'Updated through admin hypermedia before storefront checkout.',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::OK, $publishedProduct->code);

        $storefrontList = $this->follow($publishedProduct, 'goProductList', ['nameKeyword' => self::$updatedProductName]);
        $this->assertSame(1, $this->bodyValue($storefrontList, 'totalItemCount'));

        $storefrontProduct = $this->follow($storefrontList, 'goProduct', ['productCode' => self::$productCode]);
        $added = $this->resource->post($this->linkHref($storefrontProduct, 'doAddCartItem'), [
            'productCode' => self::$productCode,
            'quantity' => 1,
            'sessionPrefix' => self::SESSION_PREFIX,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $added->code);

        $cart = $this->follow($added, 'goCart', ['sessionPrefix' => self::SESSION_PREFIX]);
        $this->assertSame(1, $this->bodyValue($cart, 'cartCount'));

        $entry = $this->resource->get($this->linkHref($cart, 'goCheckoutEntry'));
        $this->assertSame(Code::SEE_OTHER, $entry->code);

        $shoppingLogin = $this->followLocation($entry, '/shopping/login');
        $nonMemberForm = $this->follow($shoppingLogin, 'goShoppingNonMember');
        $submitted = $this->resource->post($this->linkHref($nonMemberForm, 'doSubmitNonMember'), [
            'name01' => 'メール',
            'name02' => '購入者',
            'kana01' => 'メール',
            'kana02' => 'コウニュウシャ',
            'email' => self::$email,
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'sessionPrefix' => self::SESSION_PREFIX,
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $submitted->code);

        $confirmed = $this->resource->post($this->linkHref($submitted, 'doConfirmOrder'), [
            'preOrderId' => $this->bodyValue($submitted, 'preOrderId'),
            'payment' => $this->bodyValue($submitted, 'paymentMethodId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::OK, $confirmed->code);

        $checkedOut = $this->resource->post($this->linkHref($confirmed, 'doCheckout'), [
            'preOrderId' => $this->bodyValue($confirmed, 'preOrderId'),
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $checkedOut->code);
        self::$orderNo = $this->bodyString($checkedOut, 'orderNo');

        $complete = $this->followLocation($checkedOut);
        $this->assertSame(self::$orderNo, $this->bodyValue($complete, 'orderNo'));

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
        $sent = $this->resource->post($this->linkHref($response, 'doSendOrderMail'), [
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
        $deleted = $this->resource->delete($this->linkHref($response, 'doDeleteMailTemplate'), [
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
