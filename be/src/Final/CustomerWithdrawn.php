<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function sprintf;

/**
 * Customer withdrawn — Final, proof the logged-in customer
 * self-cancelled their account.
 *
 *   WithdrawCustomerInput → CustomerWithdrawn (this stage)
 *
 * Multi-side-effect convergence (Pilot 5 convention): existence of
 * this object proves a strict sequence of durable effects has run,
 * all in this constructor:
 *
 *   1. Capture the ORIGINAL email + name fields (for the mail body
 *      that goes to the human who still owns the address — must run
 *      before step 2 overwrites the record).
 *   2. Replace the customer record:
 *        - email → `withdrawn-{customerId}@example.invalid` (the
 *          `.invalid` TLD is reserved by RFC 2606 and can never
 *          collide with a real address, freeing the original email
 *          slot so the human can re-register later)
 *        - customerStatus → STATUS_WITHDRAWN (3)
 *   3. Clear every session-scoped cart via
 *      CartCommandInterface::clearBySessionPrefix (one customer can
 *      hold N carts, one per saleType, so we wipe the whole prefix).
 *   4. Send the withdrawal-confirmation email to the ORIGINAL email
 *      (Mailer is non-throwing by contract, so the order matters: the
 *      record-of-truth flip happens first).
 *
 * AUTHN: customerId comes from SessionInterface. A null session — or
 * a session pointing to a non-existent customer — raises
 * UnauthenticatedException (Pilot 8 lesson: do not leak existence at
 * the AAA boundary). The BEAR layer maps this to 401.
 *
 * Idempotency (ALPS `type=idempotent`): a second withdrawal call by
 * the same customer is a no-op. We detect "already withdrawn" by
 * customerStatus===STATUS_WITHDRAWN(3) and short-circuit — no second
 * mail, no second cart-clear. The Final still constructs successfully
 * so the resource layer returns 200 on replay. (In production the
 * session is cleared by the EventListener after step 4, so a normal
 * UI flow never replays; this branch exists for retries and
 * test-determinism.)
 *
 * Session clear is the EC-CUBE EventListener's job (Slice 7.2
 * contract — same as Pilot 6 doLogin / doLogout). The Be layer does
 * NOT touch session storage.
 */
final readonly class CustomerWithdrawn
{
    /** Withdrawn customer status (EC-CUBE dtb_customer.customer_status_id=3). */
    public const int STATUS_WITHDRAWN = 3;

    public string $customerId;
    public string $originalEmail;
    public string $dummyEmail;
    public bool $cleared;

    public function __construct(
        #[Input] string $sessionPrefix,
        #[Inject] SessionInterface $session,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] CustomerCommandInterface $customerCommand,
        #[Inject] CartCommandInterface $cartCommand,
        #[Inject] MailerInterface $mailer,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $current = $customerQuery->findById($sessionCustomerId);
        if ($current === null) {
            // Session points to a non-existent customer (deleted /
            // expired). Mirror Pilot 8: treat as not-logged-in to
            // avoid leaking existence across the AAA boundary.
            throw new UnauthenticatedException();
        }

        // Step 1: capture the originals BEFORE step 2 overwrites them.
        // These travel into the mail body and the public surface.
        $originalEmail = $current->email;
        $name01 = $current->name01;
        $name02 = $current->name02;

        // Idempotency short-circuit: a customer already in the
        // withdrawn terminal state replays as a no-op.
        if ($current->customerStatus === self::STATUS_WITHDRAWN) {
            $this->customerId = $current->customerId;
            $this->originalEmail = $originalEmail;
            $this->dummyEmail = $current->email;
            $this->cleared = true;

            return;
        }

        $dummyEmail = sprintf('withdrawn-%s@example.invalid', $current->customerId);

        // Step 2: persist the withdrawn shape (record of truth FIRST,
        // mirroring CheckoutCompleted's order convention).
        $withdrawn = new CustomerEntity(
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
        $customerCommand->update($withdrawn);

        // Step 3: wipe every session-scoped cart for this customer.
        $cartCommand->clearBySessionPrefix($sessionPrefix);

        // Step 4: notify the human at the ORIGINAL address.
        $mailer->sendWithdrawConfirmation($originalEmail, $name01, $name02);

        $this->customerId = $current->customerId;
        $this->originalEmail = $originalEmail;
        $this->dummyEmail = $dummyEmail;
        $this->cleared = true;
    }
}
