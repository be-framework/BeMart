<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;

/**
 * EC-CUBE admin Product/new — 商品登録フォーム。
 *
 * The write-side API already exists as Admin\Product::onPost(); this
 * page is the missing browser UI entry. It is intentionally a first
 * slice: fields are limited to the current AdminCreateProductInput body
 * contract (code/name/price/stock/status/description/searchWord/note).
 */
final class ProductNew extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly CsrfToken $csrf,
    ) {
    }

    #[Link(rel: 'doCreateProduct', href: 'page://self/admin/product', method: 'post')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'csrfToken' => $this->csrf->token,
            'productStatusOptions' => [
                1 => '公開',
                2 => '非公開',
                3 => '廃止',
            ],
        ];

        return $this;
    }
}
