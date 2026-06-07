<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class ShoppingShippingResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetShippingReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingShipping', $ro->body['transitionId']);
        $this->assertSame(['shippingAddressId', 'csrfToken'], $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/shopping/shipping', $ro->body['submitTo']['href']);
        // Address data lookup is a Wave-future TODO — empty list for now.
        $this->assertSame([], $ro->body['addresses']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnGetShippingEditReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingShippingEdit', $ro->body['transitionId']);
        $this->assertContains('name01', $ro->body['fields']);
        $this->assertContains('postalCode', $ro->body['fields']);
        $this->assertContains('addr01', $ro->body['fields']);
        $this->assertContains('csrfToken', $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/shopping/shipping-edit', $ro->body['submitTo']['href']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnGetShippingMultipleReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-multiple');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingShippingMultiple', $ro->body['transitionId']);
        $this->assertSame([], $ro->body['fields']);
        $this->assertNull($ro->body['submitTo']);
        // Cart-item × address allocation is a Wave-future TODO.
        $this->assertSame([], $ro->body['cartItems']);
        $this->assertSame([], $ro->body['addresses']);
        $this->assertSame('page://self/shopping', $ro->body['links']['goShopping']);
    }

    public function testOnPostShippingSelectsAddress(): void
    {
        $ro = $this->resource->post('page://self/shopping/shipping', [
            'shippingAddressId' => '1',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/shopping', $ro->headers['Location']);
        $this->assertSame('doSelectShippingAddress', $ro->body['transitionId']);
        $this->assertSame('1', $ro->body['shippingAddressId']);
    }

    public function testOnPostShippingEditAcceptsAddressFields(): void
    {
        $ro = $this->resource->post('page://self/shopping/shipping-edit', [
            'name01' => '田中',
            'name02' => '太郎',
            'kana01' => 'タナカ',
            'kana02' => 'タロウ',
            'companyName' => 'Example Inc.',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/shopping', $ro->headers['Location']);
        $this->assertSame('doUpdateShippingAddress', $ro->body['transitionId']);
        $this->assertSame('田中', $ro->body['name01']);
        $this->assertSame(13, $ro->body['pref']);
    }

    public function testOnPostShippingMultipleAcceptsEmptyAllocation(): void
    {
        $ro = $this->resource->post('page://self/shopping/shipping-multiple', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/shopping', $ro->headers['Location']);
        $this->assertSame('doSelectShippingAddress', $ro->body['transitionId']);
        $this->assertSame(0, $ro->body['allocationCount']);
    }

    public function testOnPostShippingMultipleEditAcceptsAddressFields(): void
    {
        $ro = $this->resource->post('page://self/shopping/shipping-multiple-edit', [
            'name01' => '佐藤',
            'name02' => '花子',
            'kana01' => 'サトウ',
            'kana02' => 'ハナコ',
            'companyName' => null,
            'postalCode' => '1600022',
            'pref' => 13,
            'addr01' => '新宿区',
            'addr02' => '新宿4-5-6',
            'phoneNumber' => '0311112222',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/shopping/shipping-multiple', $ro->headers['Location']);
        $this->assertSame('doUpdateShippingAddress', $ro->body['transitionId']);
        $this->assertSame('佐藤', $ro->body['name01']);
        $this->assertSame('新宿区', $ro->body['addr01']);
    }
}
