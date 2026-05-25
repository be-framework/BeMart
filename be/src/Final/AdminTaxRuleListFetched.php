<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin tax-rule list fetched — Final, the back-office view of every
 * tax-rule master row (Wave 9θ).
 *
 *   GetAdminTaxRuleListInput → AdminTaxRuleListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
final readonly class AdminTaxRuleListFetched
{
    public int $count;

    /**
     * @var list<array{
     *     taxRuleId: string,
     *     taxRate: float,
     *     roundingType: int,
     *     applyDate: string,
     * }>
     */
    public array $taxRules;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] TaxRuleStorageInterface $taxRules,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $taxRules->list();

        $this->count = count($rows);
        $this->taxRules = array_map(
            static fn (TaxRuleEntity $row): array => [
                'taxRuleId' => $row->taxRuleId,
                'taxRate' => $row->taxRate,
                'roundingType' => $row->roundingType,
                'applyDate' => $row->applyDate,
            ],
            $rows,
        );
    }
}
