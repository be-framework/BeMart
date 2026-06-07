<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Final\ProductFetched;
use MyVendor\BeMart\Be\Input\GetProductInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AddCartForm;
use MyVendor\BeMart\Support\ProductImageCatalog;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goProduct —商品詳細ページ。
 *
 * Resource is the HTTP entry point: it builds a Be Input, hands it to
 * Becoming, and projects the resulting Final into the response body.
 * All validation and DB access live in the Be domain layer.
 *
 * Phase 3 — HTML page. The product detail page carries the add-to-cart
 * action, which EC-CUBE renders as a FORM (`AddCartType` — quantity +,
 * for class products, the product-class selects). The resource builds
 * an {@see AddCartForm} (Ray.WebFormModule AbstractForm), seeds its
 * hidden `product_id` with the product code, and exposes it as
 * `body['form']` so the HTML port can render the real quantity
 * `<input>` via `{{ form.input('quantity') }}`. The form is a
 * field-definition + renderer only — VALIDATION AUTHORITY STAYS WITH the
 * Be Framework Becoming chain (the Cart add-item Input). JSON contexts
 * (`app`, `prod`, `test`) ignore `body['form']`; the JSON-context tests
 * assert key-wise on `body` and are unaffected.
 *
 * FormFactory is self-sufficient (no Ray.Di bindings needed), so the
 * resource builds the form in every context cheaply; only the `html`
 * context's TwigRenderer actually renders it.
 */
class Product extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
        private readonly CsrfToken $csrf,
    ) {
    }

    /**
     * Phase B Slice 9: `$productCode` is user input (URI / query param);
     * declared explicitly so Psalm taint analysis can trace it through
     * Becoming into any downstream sink. The Be Semantic\ProductCode
     * constructor format-validates but does not escape — sinks downstream
     * still need to defend (e.g. bound parameters for SQL).
     *
     * @psalm-taint-source input $productCode
     */
    #[Alps('goProduct')]
    #[JsonSchema(schema: 'get-product.json', params: 'get-product.param.json')]
    #[Link(rel: 'goProductList', href: 'page://self/products')]
    #[Link(rel: 'doAddCartItem', href: 'page://self/cart/item', method: 'post')]
    #[Link(rel: 'doAddFavorite', href: 'page://self/mypage/favorite', method: 'post')]
    #[Link(rel: 'doRemoveFavorite', href: 'page://self/mypage/favorite', method: 'delete')]
    public function onGet(string $productCode): static
    {
        $final = ($this->becoming)(new GetProductInput($productCode));

        assert($final instanceof ProductFetched);

        $this->body = [
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'price02' => $final->price02,
            'stock' => $final->stock,
            // EC-CUBE's detail.twig branches the add-cart form vs the
            // "out of stock" button on `Product.stock_find`. BeMart's
            // body has the raw `stock` count; `stockFind` is the derived
            // purchasable flag (null stock = 在庫無制限 -> always true).
            'stockFind' => $final->stock === null || $final->stock > 0,
            'description' => $final->description,
            'categoryNames' => $final->categoryNames,
            'tagNames' => $final->tagNames,
            'classNames' => $final->classNames,
            'mainImage' => $final->imagePath ?? ProductImageCatalog::forProductCode($final->productCode),
            // Phase 3: the add-to-cart form. EC-CUBE renders the add-cart
            // quantity input through `AddCartType`; BeMart renders it
            // through this AddCartForm. The hidden `product_id` is seeded
            // with the product code. JSON contexts ignore `form`.
            'form' => $this->addCartForm($final->productCode),
            // CSRF reference for the add-to-cart POST: the HTML port
            // renders it into the form's hidden `_token` input so the
            // POST to `page://self/cart/item` passes CSRF validation.
            'csrfToken' => $this->csrf->token,
        ];

        return $this;
    }

    /**
     * Builds an AddCartForm for the given product.
     *
     * The hidden `product_id` is seeded with the product code so the
     * POST carries the product identity, and `quantity` keeps its
     * EC-CUBE default of 1. Validation authority stays with Be — the
     * form is a renderer here, never a validator.
     */
    private function addCartForm(string $productCode): AddCartForm
    {
        $form = $this->formFactory->newInstance(AddCartForm::class);
        assert($form instanceof AddCartForm);

        $form->fillValues([
            'product_id' => $productCode,
            'csrfToken' => $this->csrf->token,
            'quantity' => 1,
        ]);

        return $form;
    }
}
