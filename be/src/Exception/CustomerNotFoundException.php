<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when an admin-side lookup queries a customer by email and no
 * such customer exists in storage. Wave 5 (goCustomer) raises this
 * after the admin firewall has already proven `adminId() !== null`.
 *
 * Distinct from {@see UnauthenticatedException} — that one is the
 * customer-firewall AUTHN-missing case ("you need to log in"). This
 * one is the admin-firewall "the email you queried does not resolve"
 * case: the admin IS authenticated, but the target customer record is
 * absent.
 *
 * Distinct from {@see UnauthorizedAdminAccessException} — that one
 * fires before existence is probed, when the request never crossed
 * the admin firewall. Sequencing matters: AUTHZ-cross-firewall is
 * checked first, then existence (same discipline as Pilot 12's
 * UnauthN → 404 → AUTHZ ladder for orders).
 *
 * Resource layer maps this to HTTP 404 by convention — same shape as
 * OrderNotFoundException for the customer firewall.
 */
#[Message([
    'en' => 'The requested customer was not found.',
    'ja' => '指定された会員は見つかりませんでした。',
])]
final class CustomerNotFoundException extends DomainException
{
}
