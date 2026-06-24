<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\ProductClassRegistered;
use MyVendor\BeMart\Be\Input\RegisterProductClassInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminProductClassForm;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Ray\Csrf\Attribute\CsrfToken;
use Ray\WebFormModule\FormFactory;

use function assert;
use function is_bool;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE 商品規格 — Product Tier-2 (`admin/Product/product_class.twig`,
 * the ~448-line product-class matrix editor).
 *
 *   GET  /admin/product/product-class?productCode=…  → class-matrix editor
 *   POST /admin/product/product-class                → doRegisterProductClass
 *
 * GET is a thin renderer. EC-CUBE's editor renders one row per
 * 規格1 × 規格2 class-category cell, each carrying its own
 * price / stock / stock-unlimited / product-code / shipping-charge
 * controls. The Be domain has no transition to READ a product's
 * ProductClass matrix — the ProductClass join is Grade-C 厳密移植 scope
 * — so this resource renders a blank "新規規格" editor (the
 * render-smoke test exercises this with empty JSON-backed fake storage), mirroring
 * the sibling {@see \MyVendor\BeMart\Resource\Page\Admin\Order\ShippingAddress}
 * GET renderer.
 *
 * POST faithfully ports the single-row 登録 (register) write: it
 * registers one ProductClass SKU for the supplied productCode via the
 * Be domain ({@see RegisterProductClassInput} → {@see ProductClassRegistered}),
 * mirroring the canonical
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Delivery\DeliveryList} onPost.
 *
 * AUTHZ: GET uses a direct admin-session check (Pattern B — no Be
 * transition is invoked on the GET path; no admin session → 403). POST
 * delegates to the Be domain, whose Final throws
 * UnauthorizedAdminAccessException before any persistence when the
 * admin session is absent.
 */
class ProductClass extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `goProductClass` に対応する GET 操作。
     * @psalm-taint-source input $productCode
     */
    #[Alps('goProductClass')]
    #[JsonSchema(schema: 'get-admin-product-product-class.json', params: 'get-admin-product-product-class.param.json')]
    #[Link(rel: 'goProduct', href: 'page://self/admin/product/edit', method: 'get')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[Link(rel: 'doRegisterProductClass', href: 'page://self/admin/product/product-class', method: 'post')]
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

    /**
     * ALPS `doRegisterProductClass` に対応する POST 操作。
     *
     * A real browser submits the EC-CUBE 新規規格 matrix row, whose Aura
     * fields are the snake_case leaf names of {@see AdminProductClassForm}:
     * `product_code` (leaf SKU), `price02`, `stock`, `stock_unlimited`
     * (scalar checkbox — present only when checked) and `delivery_fee`.
     * The parent product is carried by the hidden camelCase `productCode`.
     * Blank money/stock fields arrive as empty strings (`price02=&stock=`),
     * which a non-nullable `int` boundary would reject with a 400, so the
     * transport empties are normalised to int here (EC-CUBE IntegerType
     * semantics). The authoritative SKU is the parent `productCode`; the
     * per-cell `product_code` leaf is accepted but not used for the write.
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $price02
     */
    #[Alps('doRegisterProductClass')]
    #[JsonSchema(schema: 'post-admin-product-product-class.json', params: 'post-admin-product-product-class.param.json')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[CsrfToken]
    public function onPost(
        string $productCode,
        int|string|null $price02 = 0,
        int|string|null $stock = 0,
        bool|string|null $stock_unlimited = false,
        int|string|null $delivery_fee = 0,
        string|null $product_code = null,
    ): static {
        $final = ($this->becoming)(new RegisterProductClassInput(
            productCode: $productCode,
            price02: $this->toInt($price02),
            stock: $this->toInt($stock),
            stockUnlimited: $this->toBool($stock_unlimited),
            deliveryFee: $this->toInt($delivery_fee),
        ));

        assert($final instanceof ProductClassRegistered);

        ($this->mutationResponse)($this, Code::CREATED, sprintf('/admin/product/product-class?productCode=%s', urlencode($final->productCode)));
        $this->body = [
            'productClassId' => $final->productClassId,
            'productCode' => $final->productCode,
        ];

        return $this;
    }

    /**
     * Coerce a transport money/stock field (`''`, `'1200'`, int) to int.
     *
     * EC-CUBE's IntegerType treats a blank field as 0; the entity needs a
     * non-nullable int, so empty string / null collapse to 0 here.
     */
    private function toInt(int|string|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    /**
     * Coerce a scalar checkbox field to bool. A browser posts the checked
     * box as `stock_unlimited=1` and omits it entirely when unchecked
     * (default `false` here); EC-CUBE truthy strings map to true.
     */
    private function toBool(bool|string|null $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return $value === '1' || $value === 'true' || $value === 'on';
    }
}
