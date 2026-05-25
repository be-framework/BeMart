<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function sprintf;

/**
 * Admin customer deleted — Final, proof an admin soft-deleted one
 * customer record.
 *
 *   AdminDeleteCustomerInput → AdminCustomerDeleted (Direct, admin AUTHZ)
 *
 * EC-CUBE convention (preserved verbatim from Wave 2G
 * {@see CustomerWithdrawn}): `dtb_customer` rows are NEVER physically
 * deleted — soft delete flips `customer_status` to 3 (Withdrawn) and
 * overwrites `email` with `withdrawn-{customerId}@example.invalid`
 * (the `.invalid` TLD is RFC 2606 reserved, so the slot never collides
 * with a real address and the human is free to re-register). The row
 * is retained for FK integrity: outstanding orders (`dtb_order`) keep
 * pointing at a real customerId — the ALPS doc text "受注は会員IDをNULLにして保持"
 * refers to a future order-level cascade that is OUT OF SCOPE here;
 * this transition only touches the customer row.
 *
 * AUTHZ — cross-firewall (Wave 4 lesson, same ladder as Wave 5
 * {@see AdminCustomerFetched}):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown customerId   → CustomerNotFoundException         (404)
 *
 * The admin firewall check happens before existence is probed so an
 * admin-anonymous client has no business learning whether a given
 * customerId resolves. The 404 distinguishes "you queried a customerId
 * that never existed" from "you queried a real but already-deleted
 * customer" — the latter is the idempotent replay case (200,
 * `alreadyDeleted=true`).
 *
 * Mass-assignment safety: the adminId is read exclusively from the
 * AdminSession; it is NOT a constructor parameter. The only request-
 * controlled input is `$customerId` (the target — admin AUTHZ replaces
 * the Pilot 5 F-2 mass-assignment guard that protects the customer-self
 * variant).
 *
 * Idempotency (ALPS `type=idempotent`): a second delete against an
 * already-withdrawn customer is a no-op — no second `update`, no
 * second mail. The Final still constructs successfully, surfaces
 * `alreadyDeleted=true`, and the resource layer maps it to 200. This
 * mirrors Wave 2G CustomerWithdrawn's idempotency branch but reuses
 * none of its code (G-17): the chain shape, AUTHZ source, and
 * cart-clear discipline all differ.
 *
 * Side effects, in strict order:
 *
 *   1. Capture the ORIGINAL email + name fields (for the mail body
 *      that goes to the human who still owns the address — must run
 *      before step 2 overwrites the record).
 *   2. Replace the customer record with status=3 + dummy email via
 *      {@see CustomerCommandInterface::update}.
 *   3. Send the goodbye mail via
 *      {@see MailerInterface::sendWithdrawConfirmation} to the
 *      ORIGINAL email (Mailer is non-throwing by contract; durable
 *      state flip runs first).
 *
 * Cart-clear deferred to Phase 2 (Wave 6 scope decision): unlike Wave
 * 2G the admin does NOT know the target customer's browser-cookie
 * `sessionPrefix`. Wiring an additional `targetSessionPrefix` plumb-
 * through would couple admin tooling to customer-session internals;
 * Phase 2 introduces a dedicated `clearByCustomerId` interface or a
 * background sweeper. For now, the customer record is the only durable
 * effect and cart entries become orphans referenced by the dummy email.
 */
final readonly class AdminCustomerDeleted
{
    /** Withdrawn customer status (EC-CUBE dtb_customer.customer_status_id=3). */
    public const int STATUS_WITHDRAWN = 3;

    public string $customerId;
    public string $originalEmail;
    public bool $alreadyDeleted;

    public function __construct(
        #[Input] string $customerId,
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] CustomerCommandInterface $customerCommand,
        #[Inject] MailerInterface $mailer,
    ) {
        // AUTHZ cross-firewall first — refuse non-admin requests before
        // probing existence (no enumeration via 404 vs 403 distinction).
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $customerQuery->item($customerId);
        if ($current === null) {
            throw new CustomerNotFoundException();
        }

        // Idempotency short-circuit: an already-withdrawn customer
        // replays as a no-op — `email` already carries the dummy
        // placeholder, so we surface it verbatim under originalEmail
        // (same convention as Wave 2G CustomerWithdrawn's replay
        // branch). No second mail, no second update.
        if ($current->customerStatus === self::STATUS_WITHDRAWN) {
            $this->customerId = $current->customerId;
            $this->originalEmail = $current->email;
            $this->alreadyDeleted = true;

            return;
        }

        // Step 1: capture the originals BEFORE step 2 overwrites them.
        // The mail body wants the human-readable name + the address
        // that still belongs to the human.
        $originalEmail = $current->email;
        $name01 = $current->name01;
        $name02 = $current->name02;

        $dummyEmail = sprintf('withdrawn-%s@example.invalid', $current->customerId);

        // Step 2: persist the withdrawn shape (record of truth FIRST,
        // mirroring CheckoutCompleted's order convention).
        $deleted = new CustomerEntity(
            customerId: $current->customerId,
            email: $dummyEmail,
            passwordHash: $current->passwordHash,
            name01: $current->name01,
            name02: $current->name02,
            kana01: $current->kana01,
            kana02: $current->kana02,
            companyName: $current->companyName,
            phoneNumber: $current->phoneNumber,
            postalCode: $current->postalCode,
            pref: $current->pref,
            addr01: $current->addr01,
            addr02: $current->addr02,
            birth: $current->birth,
            sex: $current->sex,
            job: $current->job,
            initialPoint: $current->initialPoint,
            customerStatus: self::STATUS_WITHDRAWN,
            secretKey: $current->secretKey,
        );
        $customerCommand->update($deleted);

        // Step 3: notify the human at the ORIGINAL address.
        $mailer->sendWithdrawConfirmation($originalEmail, $name01, $name02);

        $this->customerId = $current->customerId;
        $this->originalEmail = $originalEmail;
        $this->alreadyDeleted = false;
    }
}
