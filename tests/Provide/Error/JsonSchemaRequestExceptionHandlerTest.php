<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Provide\Error;

use BEAR\Resource\Exception\JsonSchemaRequestException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\JsonSchema\JsonSchemaErrors;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Provide\Error\JsonSchemaRequestExceptionHandler;
use MyVendor\BeMart\Provide\Error\ValidationException;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * Drives the request handler directly (no DI / DB). Pins this codebase's
 * contribution on top of bear/resource's structured request errors: errors are
 * grouped by field path, each field gets a Japanese label from its schema
 * `title` (a schema `errorMessage` wins when present), nested/array paths fall
 * back to their root segment's title, and an empty error set preserves the
 * original signal rather than masking it.
 */
final class JsonSchemaRequestExceptionHandlerTest extends TestCase
{
    private const PRODUCT_SCHEMA = '/var/json_validate/put-admin-product.param.json';
    private const ORDER_SCHEMA = '/var/json_validate/post-admin-order-create.param.json';

    /** Top-level failing field is labelled by its Japanese schema `title`. */
    public function testGroupsByFieldWithJapaneseTitleLabel(): void
    {
        $e = $this->dispatch(self::PRODUCT_SCHEMA, [
            new JsonSchemaError('productName', '/productName', 'too long', new ConstraintViolation('maxLength', ['maxLength' => 128])),
        ]);

        $this->assertSame(['productName' => ['商品名（入力）を確認してください']], $e->errors);
        $this->assertStringContainsString('商品名（入力）', $e->getMessage());
    }

    /** A nested/array path with no top-level title falls back to its root segment's title. */
    public function testNestedArrayPathFallsBackToRootSegmentTitle(): void
    {
        $e = $this->dispatch(self::ORDER_SCHEMA, [
            new JsonSchemaError('orderItems[0].productCode', '/orderItems/0/productCode', 'required', new ConstraintViolation('required', ['property' => 'productCode'])),
        ]);

        // Key stays the precise machine path; the human label is the root title.
        $this->assertSame(['orderItems[0].productCode' => ['受注明細一覧（入力）を確認してください']], $e->errors);
        $this->assertStringContainsString('受注明細一覧（入力）', $e->getMessage());
        $this->assertStringNotContainsString('orderItems[0]', $e->getMessage());
    }

    /** A schema-side errorMessage (isCustomMessage) wins over the title fallback. */
    public function testSchemaErrorMessageWinsOverTitleFallback(): void
    {
        $e = $this->dispatch(self::PRODUCT_SCHEMA, [
            new JsonSchemaError('productName', '/productName', '商品名は必須です。', new ConstraintViolation('required', ['property' => 'productName']), 'Property productName is required'),
        ]);

        $this->assertSame(['productName' => ['商品名は必須です。']], $e->errors);
    }

    /** A root-level ('') failure is keyed `_root` and labelled generically. */
    public function testRootErrorUsesRootKeyAndLabel(): void
    {
        $e = $this->dispatch(self::PRODUCT_SCHEMA, [
            new JsonSchemaError('', '', 'must be object', new ConstraintViolation('type', ['type' => 'object'])),
        ]);

        $this->assertArrayHasKey('_root', $e->errors);
        $this->assertStringContainsString('入力', $e->getMessage());
    }

    /** Several errors on the same field are preserved in order. */
    public function testMultipleErrorsForSameFieldArePreserved(): void
    {
        $e = $this->dispatch(self::PRODUCT_SCHEMA, [
            new JsonSchemaError('productName', '/productName', '必須です。', new ConstraintViolation('required', []), 'required'),
            new JsonSchemaError('productName', '/productName', '128文字以内です。', new ConstraintViolation('maxLength', ['maxLength' => 128]), 'too long'),
        ]);

        $this->assertSame(['必須です。', '128文字以内です。'], $e->errors['productName']);
    }

    /** No structured errors → rethrow the original request exception, not an empty ValidationException. */
    public function testEmptyErrorsRethrowsOriginalException(): void
    {
        $original = new JsonSchemaRequestException('empty', 400, new JsonSchemaErrors([]));

        $this->expectExceptionObject($original);
        (new JsonSchemaRequestExceptionHandler())->handleRequestException(
            [],
            $this->ro(),
            $original,
            dirname(__DIR__, 3) . self::PRODUCT_SCHEMA,
        );
    }

    /** @param list<JsonSchemaError> $errors */
    private function dispatch(string $schema, array $errors): ValidationException
    {
        try {
            (new JsonSchemaRequestExceptionHandler())->handleRequestException(
                [],
                $this->ro(),
                new JsonSchemaRequestException('invalid', 400, new JsonSchemaErrors($errors)),
                dirname(__DIR__, 3) . $schema,
            );
        } catch (ValidationException $e) {
            return $e;
        }

        $this->fail('Expected ValidationException');
    }

    private function ro(): ResourceObject
    {
        return new class extends ResourceObject {
        };
    }
}
