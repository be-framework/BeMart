<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown by the {@see \MyVendor\BeMart\Be\Semantic\DeviceToken} Semantic
 * when the submitted 2FA code is not a 6-digit string. A syntactically
 * invalid code can never verify, so it is rejected at the ontology
 * boundary (Be Framework wraps this in a SemanticVariableException) —
 * distinct from the credential-mismatch
 * {@see TwoFactorAuthFailedException} the Final raises.
 */
#[Message([
    'en' => 'The authentication code must be 6 digits.',
    'ja' => '認証コードは6桁の数字で入力してください。',
])]
final class DeviceTokenFormatException extends DomainException
{
}
