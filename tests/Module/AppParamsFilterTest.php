<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Module\AppParamsFilter;
use PHPUnit\Framework\TestCase;

/**
 * Unit-level, unlike {@see \MyVendor\BeMart\Tests\Module\EventSourcingExtractionTest}'s
 * integration coverage: those tests always send resetKey/authKey alongside password/deviceToken,
 * both of which the library default already marks non-replayable on their own — passing there
 * does not prove AppParamsFilter's own replayable=false branch fired for resetKey/authKey
 * specifically. Each case here sends exactly one app-specific key, nothing the library default
 * would touch.
 */
final class AppParamsFilterTest extends TestCase
{
    public function testResetKeyAloneIsRemovedAndMarksNonReplayable(): void
    {
        $filter = new AppParamsFilter();

        $result = ($filter)(['resetKey' => 'x', 'orderId' => 'O-1']);

        $this->assertSame(['orderId' => 'O-1'], $result->params);
        $this->assertFalse($result->replayable);
    }

    public function testAuthKeyAloneIsRemovedAndMarksNonReplayable(): void
    {
        $filter = new AppParamsFilter();

        $result = ($filter)(['authKey' => 'x', 'orderId' => 'O-1']);

        $this->assertSame(['orderId' => 'O-1'], $result->params);
        $this->assertFalse($result->replayable);
    }

    public function testAKeySuffixedFieldTheLibraryDefaultIgnoresStaysUntouched(): void
    {
        // Confirms AppParamsFilter's exact-name list does not widen into a blanket `key` suffix:
        // an idempotencyKey must survive, replayable, exactly like it does through the bare
        // library default.
        $filter = new AppParamsFilter();

        $result = ($filter)(['idempotencyKey' => 'abc-123']);

        $this->assertSame(['idempotencyKey' => 'abc-123'], $result->params);
        $this->assertTrue($result->replayable);
    }

    public function testAlreadyNonReplayableStaysNonReplayableWhenNothingAppSpecificIsFound(): void
    {
        $filter = new AppParamsFilter();

        $result = ($filter)(['password' => 'x']);

        $this->assertArrayNotHasKey('password', $result->params);
        $this->assertFalse($result->replayable);
    }
}
