<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\BaseInfoFetched;
use MyVendor\BeMart\Be\Final\BaseInfoUpdated;
use MyVendor\BeMart\Be\Input\GetBaseInfoInput;
use MyVendor\BeMart\Be\Input\UpdateBaseInfoInput;
use MyVendor\BeMart\Form\AdminShopMasterForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE doUpdateBaseInfo + goBaseInfo — 基本情報 (Wave 8 + Wave 9).
 *
 *   - GET  → goBaseInfo (safe read, admin AUTHZ, Wave 9ι)
 *   - POST → doUpdateBaseInfo (idempotent, admin AUTHZ + CSRF, Wave 8ε)
 *
 * dtb_base_info is a single-row table; POST replaces the row wholesale
 * (no per-field PATCH semantic in EC-CUBE). Only the shopName is
 * required — null in other fields means "clear it".
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403 (POST only)
 *   - SemanticVariableException             → 400 (shopName / address /
 *                                                phoneNumber / … format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *
 * Idempotency (ALPS `type=idempotent`): replaying the same body is a
 * no-op-equivalent — the Final reports `changed=false` and the row
 * is not rewritten.
 *
 * Mass-assignment safety: only the shop-info columns are accepted.
 */
class BaseInfo extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Wave 9ι: goBaseInfo — admin views the shop base info form data.
     *
     * Setting/Shop Tier-2 also renders `shop_master.twig` from this body;
     * the `form` key carries an {@see AdminShopMasterForm} pre-filled
     * with the dtb_base_info row for the HTML editor.
     */
    #[Link(rel: 'doUpdateBaseInfo', href: 'page://self/admin/base-info', method: 'post')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetBaseInfoInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof BaseInfoFetched);

        $form = $this->formFactory->newInstance(AdminShopMasterForm::class);
        assert($form instanceof AdminShopMasterForm);
        $form->fillValues([
            'shop_name' => $final->shopName,
            'shop_kana' => $final->shopKana,
            'shop_name_eng' => $final->shopNameEng,
            'company_name' => $final->companyName,
            'postal_code' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
            'phone_number' => $final->phoneNumber,
            'business_hour' => $final->businessHour,
            'email01' => $final->shopEmail01,
            'shop_message' => $final->shopMessage,
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'shopName' => $final->shopName,
            'shopKana' => $final->shopKana,
            'shopNameEng' => $final->shopNameEng,
            'companyName' => $final->companyName,
            'postalCode' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
            'phoneNumber' => $final->phoneNumber,
            'businessHour' => $final->businessHour,
            'shopEmail01' => $final->shopEmail01,
            'shopMessage' => $final->shopMessage,
        ];

        return $this;
    }

    /**
     * Wave 8: every shop-info field is admin-form input.
     *
     * @psalm-taint-source input $shopName
     * @psalm-taint-source input $shopKana
     * @psalm-taint-source input $shopNameEng
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $businessHour
     * @psalm-taint-source input $shopEmail01
     * @psalm-taint-source input $shopMessage
     */
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    #[Link(rel: 'goPaymentList', href: 'page://self/admin/payment/payment-list')]
    #[CsrfProtected]
    public function onPost(
        string $shopName,
        string|null $shopKana = null,
        string|null $shopNameEng = null,
        string|null $companyName = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $phoneNumber = null,
        string|null $businessHour = null,
        string|null $shopEmail01 = null,
        string|null $shopMessage = null,
    ): static {
        try {
            $final = ($this->becoming)(new UpdateBaseInfoInput(
                shopName: $shopName,
                shopKana: $shopKana,
                shopNameEng: $shopNameEng,
                companyName: $companyName,
                postalCode: $postalCode,
                pref: $pref,
                addr01: $addr01,
                addr02: $addr02,
                phoneNumber: $phoneNumber,
                businessHour: $businessHour,
                shopEmail01: $shopEmail01,
                shopMessage: $shopMessage,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof BaseInfoUpdated);

        $this->code = Code::OK;
        $this->body = [
            'shopName' => $final->shopName,
            'shopKana' => $final->shopKana,
            'shopNameEng' => $final->shopNameEng,
            'companyName' => $final->companyName,
            'postalCode' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
            'phoneNumber' => $final->phoneNumber,
            'businessHour' => $final->businessHour,
            'shopEmail01' => $final->shopEmail01,
            'shopMessage' => $final->shopMessage,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
