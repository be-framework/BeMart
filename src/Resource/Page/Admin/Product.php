<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCreated;
use MyVendor\BeMart\Be\Final\AdminProductDeleted;
use MyVendor\BeMart\Be\Final\AdminProductFetched;
use MyVendor\BeMart\Be\Final\AdminProductUpdated;
use MyVendor\BeMart\Be\Input\AdminCreateProductInput;
use MyVendor\BeMart\Be\Input\AdminDeleteProductInput;
use MyVendor\BeMart\Be\Input\AdminUpdateProductInput;
use MyVendor\BeMart\Be\Input\GetAdminProductInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE admin product surface — combines goProduct (admin variant),
 * doCreateProduct, doUpdateProduct, doDeleteProduct in one
 * ResourceObject keyed at `page://self/admin/product`.
 *
 * Method routing:
 *   - onGet    — goProduct (admin variant) → 200 / 403 / 404
 *   - onPost   — doCreateProduct           → 201 / 400 / 403 / 409
 *   - onPut    — doUpdateProduct           → 200 / 400 / 403 / 404
 *   - onDelete — doDeleteProduct           → 200 (incl. alreadyDeleted) / 400 / 403 / 404
 *
 * The customer-facing Product.php (Pilot 1) lives at
 * `page://self/product` — a sibling resource for the consumer path
 * (shallow body, no AUTHZ). This admin resource surfaces the full
 * ProductEntity including the admin-only columns (note, searchWord,
 * productStatus).
 *
 * CSRF: enforced on every state-changing method (POST/PUT/DELETE).
 * The onGet path is read-only and skips CSRF (same convention as
 * AdminCustomer onGet).
 */
class Product extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * goProduct (admin variant) — fetch full product detail.
     *
     * @psalm-taint-source input $productCode
     */
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[Link(rel: 'doUpdateProduct', href: 'page://self/admin/product', method: 'put')]
    #[Link(rel: 'doDeleteProduct', href: 'page://self/admin/product', method: 'delete')]
    #[Link(rel: 'doCopyProduct', href: 'page://self/admin/product-copy', method: 'post')]
    public function onGet(string $productCode): static
    {
        try {
            $final = ($this->becoming)(new GetAdminProductInput(productCode: $productCode));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (ProductNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された商品が見つかりません。'];

            return $this;
        }

        assert($final instanceof AdminProductFetched);

        $this->code = Code::OK;
        $this->body = [
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'price02' => $final->price02,
            'stock' => $final->stock,
            'productStatus' => $final->productStatus,
            'description' => $final->description,
            'searchWord' => $final->searchWord,
            'note' => $final->note,
            'mainImage' => $final->imagePath,
            'categoryNames' => $final->categoryNames,
            'tagNames' => $final->tagNames,
            'classNames' => $final->classNames,
            'csrfToken' => $this->csrf->getToken(),
            'productStatusOptions' => [
                1 => '公開',
                2 => '非公開',
                3 => '廃止',
            ],
        ];

        return $this;
    }

    /**
     * doCreateProduct — create a new product.
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $productName
     * @psalm-taint-source input $price02
     * @psalm-taint-source input $stock
     * @psalm-taint-source input $productStatus
     * @psalm-taint-source input $description
     * @psalm-taint-source input $searchWord
     * @psalm-taint-source input $note
     * @psalm-taint-source input $csrfToken
     */
    public function onPost(
        string $productCode,
        string $productName,
        int $price02,
        int|null $stock = null,
        int|null $productStatus = null,
        string|null $description = null,
        string|null $searchWord = null,
        string|null $note = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminCreateProductInput(
                productCode: $productCode,
                productName: $productName,
                price02: $price02,
                stock: $stock,
                productStatus: $productStatus,
                description: $description,
                searchWord: $searchWord,
                note: $note,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'productCode' => $productCode,
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (ProductCodeAlreadyInUseException) {
            // BEAR\Resource\Code lacks CONFLICT; same integer-literal
            // convention as Pilot 4 Entry / Wave 5O AdminCreateCustomer.
            $this->code = 409;
            $this->body = ['message' => 'この商品コードは既に使用されています。', 'productCode' => $productCode];

            return $this;
        }

        assert($final instanceof AdminProductCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/product?productCode=%s', urlencode($final->productCode));
        $this->body = [
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'price02' => $final->price02,
            'stock' => $final->stock,
            'productStatus' => $final->productStatus,
            'description' => $final->description,
        ];

        return $this;
    }

    /**
     * doUpdateProduct — edit an existing product (partial overwrite).
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $productName
     * @psalm-taint-source input $price02
     * @psalm-taint-source input $stock
     * @psalm-taint-source input $productStatus
     * @psalm-taint-source input $description
     * @psalm-taint-source input $searchWord
     * @psalm-taint-source input $note
     * @psalm-taint-source input $csrfToken
     */
    public function onPut(
        string $productCode,
        string|null $productName = null,
        int|null $price02 = null,
        int|null $stock = null,
        int|null $productStatus = null,
        string|null $description = null,
        string|null $searchWord = null,
        string|null $note = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminUpdateProductInput(
                productCode: $productCode,
                productName: $productName,
                price02: $price02,
                stock: $stock,
                productStatus: $productStatus,
                description: $description,
                searchWord: $searchWord,
                note: $note,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (ProductNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された商品が見つかりません。'];

            return $this;
        }

        assert($final instanceof AdminProductUpdated);

        $this->code = Code::OK;
        $this->headers['Location'] = sprintf('/admin/product?productCode=%s', urlencode($final->productCode));
        $this->body = [
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'price02' => $final->price02,
            'stock' => $final->stock,
            'productStatus' => $final->productStatus,
            'description' => $final->description,
        ];

        return $this;
    }

    /**
     * doDeleteProduct — soft-delete (status=3). Idempotent replay
     * surfaces `alreadyDeleted=true`.
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $csrfToken
     */
    public function onDelete(
        string $productCode,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminDeleteProductInput(productCode: $productCode));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (ProductNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された商品が見つかりません。'];

            return $this;
        }

        assert($final instanceof AdminProductDeleted);

        $this->code = Code::OK;
        $this->body = [
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'alreadyDeleted' => $final->alreadyDeleted,
            'message' => $final->alreadyDeleted
                ? '指定された商品は既に削除されています。'
                : '商品を削除しました。',
        ];

        return $this;
    }
}
