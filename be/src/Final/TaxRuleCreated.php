<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;
use MyVendor\BeMart\Be\Reason\Query\TaxRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\TaxRuleIdGeneratorInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Tax rule created — Final, proof a new tax-rule master row was
 * persisted (Wave 9θ).
 *
 *   CreateTaxRuleInput → TaxRuleCreated (Direct, admin AUTHZ)
 */
final readonly class TaxRuleCreated
{
    public string $taxRuleId;
    public float $taxRate;
    public int $roundingType;
    public string $applyDate;

    public function __construct(
        #[Input] float $taxRate,
        #[Input] string $applyDate,
        #[Input] int $roundingType,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] TaxRuleStorageInterface $taxRules,
        #[Inject] TaxRuleIdGeneratorInterface $idGenerator,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new TaxRuleEntity(
            taxRuleId: $idGenerator->generate()->value(),
            taxRate: $taxRate,
            roundingType: $roundingType,
            applyDate: $applyDate,
        );

        $taxRules->put($entity);

        $this->taxRuleId = $entity->taxRuleId;
        $this->taxRate = $entity->taxRate;
        $this->roundingType = $entity->roundingType;
        $this->applyDate = $entity->applyDate;
    }
}
