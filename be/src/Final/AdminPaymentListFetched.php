<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin payment-method list fetched — Final, the back-office view of
 * every payment-method master row (Wave 9θ).
 *
 *   GetAdminPaymentListInput → AdminPaymentListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
final readonly class AdminPaymentListFetched
{
    public int $count;

    /**
     * @var list<array{
     *     paymentId: string,
     *     paymentMethodName: string,
     *     charge: int,
     *     ruleMin: int|null,
     *     ruleMax: int|null,
     *     visible: bool,
     * }>
     */
    public array $payments;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] PaymentMethodAdminStorageInterface $payments,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $payments->list();

        $this->count = count($rows);
        $this->payments = array_map(
            static fn (PaymentMethodAdminEntity $row): array => [
                'paymentId' => $row->paymentId,
                'paymentMethodName' => $row->paymentMethodName,
                'charge' => $row->charge,
                'ruleMin' => $row->ruleMin,
                'ruleMax' => $row->ruleMax,
                'visible' => $row->visible,
            ],
            $rows,
        );
    }
}
