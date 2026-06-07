<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\TaxRule;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminTaxRuleListFetched;
use MyVendor\BeMart\Be\Final\TaxRuleCreated;
use MyVendor\BeMart\Be\Input\CreateTaxRuleInput;
use MyVendor\BeMart\Be\Input\GetAdminTaxRuleListInput;
use MyVendor\BeMart\Form\AdminTaxRuleForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goTaxRuleList + doCreateTaxRule — collection endpoint
 * (Wave 9θ).
 *
 *   - GET  → goTaxRuleList    (admin lists tax rules — safe read)
 *   - POST → doCreateTaxRule  (admin adds a new tax rule)
 *
 * Per the alps.json profile, there is NO `doUpdateTaxRule` — edits flow
 * as delete + create so the applyDate audit trail remains explicit.
 * The single-row affordance (`doDeleteTaxRule`) lives at
 * `page://self/admin/tax-rule/tax-rule`.
 */
class TaxRuleList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /** ALPS `goTaxRuleList` に対応する GET 操作。 */
    #[Alps('goTaxRuleList')]
    #[JsonSchema(schema: 'get-admin-tax-rule-tax-rule-list.json')]
    #[Link(rel: 'doCreateTaxRule', href: 'page://self/admin/tax-rule/tax-rule-list', method: 'post')]
    #[Link(rel: 'doDeleteTaxRule', href: 'page://self/admin/tax-rule/tax-rule', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminTaxRuleListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminTaxRuleListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'taxRules' => $final->taxRules,
        ];
        // Phase 3: an empty AdminTaxRuleForm for the HTML list page
        // (var/templates/Page/Admin/TaxRule/TaxRuleList.html.twig) to
        // render the inline-create inputs via `{{ form.input(...) }}`.
        // The form is a renderer here, never a validator — VALIDATION
        // AUTHORITY STAYS WITH the Be Becoming chain. JSON contexts
        // (`app`, `prod`, `test`) ignore `body['form']`; the resource
        // tests assert key-wise on `body`.
        $this->body['form'] = $this->formFactory->newInstance(AdminTaxRuleForm::class);

        return $this;
    }

    /**
     * ALPS `doCreateTaxRule` に対応する POST 操作。
     * @psalm-taint-source input $taxRate
     * @psalm-taint-source input $applyDate
     * @psalm-taint-source input $roundingType
     */
    #[Alps('doCreateTaxRule')]
    #[JsonSchema(schema: 'post-admin-tax-rule-tax-rule-list.json', params: 'post-admin-tax-rule-tax-rule-list.param.json')]
    #[Link(rel: 'goTaxRuleList', href: 'page://self/admin/tax-rule/tax-rule-list')]
    #[CsrfProtected]
    public function onPost(
        float $taxRate,
        string $applyDate,
        int $roundingType = 1,
    ): static {
        try {
            $final = ($this->becoming)(new CreateTaxRuleInput(
                taxRate: $taxRate,
                applyDate: $applyDate,
                roundingType: $roundingType,
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

        assert($final instanceof TaxRuleCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/tax-rule/tax-rule?taxRuleId=%s', urlencode($final->taxRuleId));
        $this->body = [
            'taxRuleId' => $final->taxRuleId,
            'taxRate' => $final->taxRate,
            'roundingType' => $final->roundingType,
            'applyDate' => $final->applyDate,
        ];

        return $this;
    }
}
