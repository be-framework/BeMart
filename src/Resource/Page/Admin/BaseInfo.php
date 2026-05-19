<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\BaseInfoUpdated;
use MyVendor\BeMart\Be\Input\UpdateBaseInfoInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateBaseInfo — 基本情報を更新する (Wave 8).
 *
 * POST. dtb_base_info is a single-row table; the request replaces
 * the row wholesale (no per-field PATCH semantic in EC-CUBE). Only
 * the shopName is required — null in other fields means "clear it".
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
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
        private readonly CsrfTokenInterface $csrf,
    ) {
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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/admin')]
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
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
