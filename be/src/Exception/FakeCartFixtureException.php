<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Fake cart fixture is invalid.',
    'ja' => 'Fake cart fixture が不正です。',
])]
final class FakeCartFixtureException extends DomainException
{
}
