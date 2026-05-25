<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CustomerSession;

/**
 * In-memory customer session snapshot.
 *
 * Tests that need a different customer override the binding with a fresh
 * instance. There is no `loginAs()` mutator: request-scope identity is a value.
 */
final readonly class FakeSession extends CustomerSession
{
}
