<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminProductClassForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 商品規格 — Product Tier-2 (`admin/Product/product_class.twig`,
 * the ~448-line product-class matrix editor).
 *
 *   GET /admin/product/product-class?productCode=…  → class-matrix editor
 *
 * Thin GET renderer. EC-CUBE's editor renders one row per
 * 規格1 × 規格2 class-category cell, each carrying its own
 * price / stock / stock-unlimited / product-code / shipping-charge
 * controls. The Be domain has no transition to READ a product's
 * ProductClass matrix — the ProductClass join is Grade-C 厳密移植 scope
 * — so this resource renders a blank "新規規格" editor (the
 * render-smoke test exercises this with empty JSON-backed fake storage), mirroring
 * the sibling {@see \MyVendor\BeMart\Resource\Page\Admin\Order\ShippingAddress}
 * GET renderer.
 *
 * AUTHZ: a direct admin-session check (Pattern B — no Be transition is
 * invoked on the GET path). No admin session → 403.
 */
class ProductClass extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $productCode
     */
    #[Link(rel: 'goProduct', href: 'page://self/admin/product/edit', method: 'get')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(string $productCode = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminProductClassForm::class);
        assert($form instanceof AdminProductClassForm);
        $form->fillValues([
            'price02' => '', 'stock' => '', 'product_code' => '', 'delivery_fee' => '',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'productCode' => $productCode,
            'classes' => [],
        ];

        return $this;
    }
}
