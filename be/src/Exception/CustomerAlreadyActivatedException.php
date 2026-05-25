<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when an admin asks to resend the email-verification
 * (full-registration) mail for a customer who is ALREADY an active
 * member. Phase 3 `doResendActivationMail` raises this after the admin
 * firewall has proven `$adminId !== null` and the email has resolved
 * to an existing customer record.
 *
 * An active customer (customerStatus = 2) carries no `secretKey` — the
 * activation token was consumed when they promoted via
 * `doActivateCustomer`. Resending an activation mail to such a customer
 * is a meaningless request: there is nothing left to activate. Surfacing
 * it as a clear 4xx (rather than a silent success) tells the admin the
 * customer-list row they clicked is no longer a 仮会員.
 *
 * Distinct from {@see CustomerNotFoundException} — that one fires when
 * the email resolves to nothing. This one fires when the email DOES
 * resolve, but the target is the wrong KIND of customer.
 *
 * Resource layer maps this to HTTP 409 (Conflict) by convention — the
 * request conflicts with the customer's current activated state.
 */
#[Message([
    'en' => 'The customer is already an active member.',
    'ja' => '指定された会員は既に本会員です。',
])]
final class CustomerAlreadyActivatedException extends DomainException
{
}
