<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\TaxRule;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TaxRuleNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TaxRuleDeleted;
use MyVendor\BeMart\Be\Input\DeleteTaxRuleInput;
use BEAR\Resource\Annotation\JsonSchema;

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
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `doDeleteTaxRule` に対応する DELETE 操作。
     * @psalm-taint-source input $taxRuleId
     */
    #[Alps('doDeleteTaxRule')]
    #[JsonSchema(schema: 'delete-admin-tax-rule-tax-rule.json', params: 'delete-admin-tax-rule-tax-rule.param.json')]
    #[Link(rel: 'goTaxRuleList', href: 'page://self/admin/tax-rule/tax-rule-list')]
    #[Link(rel: 'goCalendar', href: 'page://self/admin/calendar')]
    #[CsrfProtected]
    public function onDelete(string $taxRuleId): static
    {
        $final = ($this->becoming)(new DeleteTaxRuleInput(taxRuleId: $taxRuleId));

        assert($final instanceof TaxRuleDeleted);

        ($this->mutationResponse)($this, Code::OK, '/admin/tax-rule/tax-rule-list');
        $this->body = ['taxRuleId' => $final->taxRuleId];

        return $this;
    }
}
