<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\TaxRule;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TaxRuleNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TaxRuleDeleted;
use MyVendor\BeMart\Be\Input\DeleteTaxRuleInput;

use function assert;

/**
 * EC-CUBE doDeleteTaxRule — single-row endpoint (Wave 9θ).
 *
 *   - DELETE → doDeleteTaxRule (admin removes a tax rule — idempotent)
 *
 * There is intentionally no `onPut` here: the alps.json profile has no
 * `doUpdateTaxRule` transition, so edits are required to flow as
 * delete-then-create.
 */
class TaxRule extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @psalm-taint-source input $taxRuleId
     */
    #[Link(rel: 'goTaxRuleList', href: 'page://self/admin/tax-rule/tax-rule-list')]
    #[CsrfProtected]
    public function onDelete(string $taxRuleId): static
    {
        try {
            $final = ($this->becoming)(new DeleteTaxRuleInput(taxRuleId: $taxRuleId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (TaxRuleNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された税率ルールは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof TaxRuleDeleted);

        $this->code = Code::OK;
        $this->body = ['taxRuleId' => $final->taxRuleId];

        return $this;
    }
}
