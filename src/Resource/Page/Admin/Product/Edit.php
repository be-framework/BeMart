<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductFetched;
use MyVendor\BeMart\Be\Input\GetAdminProductInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminProductEditForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE 商品登録 / 商品編集 — Product Tier-2 (`admin/Product/product.twig`,
 * the ~932-line multi-tab product editor).
 *
 *   GET /admin/product/edit                  → blank "new product" editor
 *   GET /admin/product/edit?productCode=…    → editor pre-filled for one product
 *
 * Thin GET renderer. The sibling JSON resource
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Product} carries the
 * `goProduct` read + `doCreateProduct` / `doUpdateProduct` /
 * `doDeleteProduct` writes; this resource is the HTML editor shell
 * only. An empty `$productCode` renders the blank editor (EC-CUBE's
 * "商品登録" path — the render-smoke test exercises this with empty
 * JSON-backed fake storage); a known productCode pre-fills; an unknown productCode
 * is 404.
 *
 * AUTHZ: the blank-editor path checks the admin session directly
 * (Pattern B — no Be transition is invoked when there is no product to
 * read); the pre-fill path delegates to {@see AdminProductFetched},
 * which raises {@see UnauthorizedAdminAccessException} for a non-admin
 * firewall. Both surface 403.
 */
class Edit extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * ALPS `goProduct` に対応する GET 操作。
     * @psalm-taint-source input $productCode
     */
    #[Alps('goProduct')]
    #[JsonSchema(schema: 'get-admin-product-edit.json', params: 'get-admin-product-edit.param.json')]
    #[Link(rel: 'doCreateProduct', href: 'page://self/admin/product', method: 'post')]
    #[Link(rel: 'doUpdateProduct', href: 'page://self/admin/product', method: 'put')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(string $productCode = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminProductEditForm::class);
        assert($form instanceof AdminProductEditForm);

        if ($productCode === '') {
            $form->fillValues([
                'name' => '', 'product_code' => '', 'price02' => '',
                'stock' => '', 'status' => '1', 'description_detail' => '',
                'search_word' => '', 'note' => '',
            ]);

            $this->code = Code::OK;
            $this->body = [
                'form' => $form,
                'productCode' => '',
                'product' => null,
            ];

            return $this;
        }

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

        $form->fillValues([
            'name' => $final->productName,
            'product_code' => $final->productCode,
            'price02' => (string) $final->price02,
            'stock' => $final->stock === null ? '' : (string) $final->stock,
            'status' => (string) $final->productStatus,
            'description_detail' => $final->description ?? '',
            'search_word' => $final->searchWord ?? '',
            'note' => $final->note ?? '',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'productCode' => $final->productCode,
            'product' => [
                'productCode' => $final->productCode,
                'productName' => $final->productName,
                'price02' => $final->price02,
                'stock' => $final->stock,
                'productStatus' => $final->productStatus,
                'description' => $final->description,
                'searchWord' => $final->searchWord,
                'note' => $final->note,
            ],
        ];

        return $this;
    }
}
