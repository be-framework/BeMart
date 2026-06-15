<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

/**
 * Resource input/output shape contracts used by Resource tests.
 *
 * Naming rule: `{resource}{HttpMethod}{Input|Status}` in lower camel case,
 * where `resource` is the page/resource name without `Page/` and `Status`
 * is the semantic response status (`Ok`, `Created`, `Error`, ...).
 */
final class ResourceSchemas
{
    public static function error(): ResourceSchema
    {
        return new ResourceSchema('Resource.error', ['message' => 'string'], allowExtra: true);
    }

    public static function productGetInput(): ResourceSchema
    {
        return new ResourceSchema('Product.onGet.input', ['productCode' => 'string']);
    }

    public static function productGetOk(): ResourceSchema
    {
        return new ResourceSchema('Product.onGet.200', [
            'productCode' => 'string',
            'productName' => 'string',
            'price02' => 'int',
            'stock' => 'int|null',
            'stockFind' => 'bool',
            'description' => 'string|null',
            'categoryNames' => 'array',
            'tagNames' => 'array',
            'classNames' => 'array',
            'mainImage' => 'string|null',
            'form' => 'object',
            'csrfToken' => 'string',
        ]);
    }

    public static function cartGetInput(): ResourceSchema
    {
        return new ResourceSchema('Cart.onGet.input', [], ['sessionPrefix' => 'string']);
    }

    public static function cartGetOk(): ResourceSchema
    {
        return new ResourceSchema('Cart.onGet.200', [
            'cartCount' => 'int',
            'totalPrice' => 'int',
            'deliveryFeeTotal' => 'int',
            'csrfToken' => 'string',
            'carts' => 'array',
        ]);
    }

    public static function loginGetOk(): ResourceSchema
    {
        return new ResourceSchema('Login.onGet.200', [
            'transitionId' => 'string',
            'fields' => 'array',
            'submitTo' => 'array',
            'csrfToken' => 'string',
            'form' => 'object',
        ]);
    }

    public static function loginPostInput(): ResourceSchema
    {
        return new ResourceSchema('Login.onPost.input', [
            'email' => 'string',
            'password' => 'string',
            'csrfToken' => 'string',
        ]);
    }

    public static function loginPostOk(): ResourceSchema
    {
        return new ResourceSchema('Login.onPost.200', [
            'customerId' => 'string',
            'email' => 'string',
            'name01' => 'string',
            'name02' => 'string',
            'customerStatus' => 'int',
        ]);
    }

    public static function adminProductGetInput(): ResourceSchema
    {
        return new ResourceSchema('AdminProduct.onGet.input', ['productCode' => 'string']);
    }

    public static function adminProductGetOk(): ResourceSchema
    {
        return new ResourceSchema('AdminProduct.onGet.200', [
            'productCode' => 'string',
            'productName' => 'string',
            'price02' => 'int',
            'stock' => 'int|null',
            'productStatus' => 'int',
            'description' => 'string|null',
            'searchWord' => 'string|null',
            'note' => 'string|null',
            'mainImage' => 'string|null',
            'categoryNames' => 'array',
            'tagNames' => 'array',
            'classNames' => 'array',
            'csrfToken' => 'string',
            'productStatusOptions' => 'array',
        ]);
    }
}
