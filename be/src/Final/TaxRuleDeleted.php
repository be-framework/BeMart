<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\TaxRuleNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Tax rule deleted — Final, proof one master row was removed
 * (Wave 9θ).
 *
 *   DeleteTaxRuleInput → TaxRuleDeleted (Direct, idempotent)
 *
 * ALPS doc note: real EC-CUBE refuses to delete the canonical
 * standard rate (id = 1). The in-memory store does NOT enforce that
 * guard in Phase 1 — the deletion is unconditional once AUTHZ +
 * existence pass. Phase 2 can add the "id=1 protected" guard once a
 * real consumer enforces the migration history.
 */
final readonly class TaxRuleDeleted
{
    public string $taxRuleId;

    public function __construct(
        #[Input] string $taxRuleId,
        #[Inject] AdminSession $adminSession,
        #[Inject] TaxRuleStorageInterface $taxRules,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($taxRules->item($taxRuleId) === null) {
            throw new TaxRuleNotFoundException();
        }

        $taxRules->delete($taxRuleId);

        $this->taxRuleId = $taxRuleId;
    }
}
