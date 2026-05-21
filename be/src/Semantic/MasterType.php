<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MasterTypeFormatException;

use function in_array;

/**
 * The admin master a generic list operation (`doSortNoMove` /
 * `doToggleVisible`) targets.
 *
 * EC-CUBE's *_sort_no_move / *_visible routes are per-master controller
 * actions; BeMart folds them into one abstract transition keyed by this
 * discriminator. The accepted set is the union of the masters ALPS
 * attaches the two generic transitions to:
 *
 *   - sort_no:  payment / delivery / tag / className / classCategory
 *   - visible:  payment / delivery / classCategory / news
 *
 * This Semantic only asserts the value is one of the known master
 * names; whether the master actually supports the requested operation
 * (e.g. `tag` has no `visible` column) is enforced downstream by
 * {@see \MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface}.
 */
final class MasterType
{
    /** @var list<string> */
    public const KNOWN = [
        'payment',
        'delivery',
        'tag',
        'className',
        'classCategory',
        'news',
    ];

    #[Validate]
    public function validate(string|null $masterType): void
    {
        if ($masterType === null) {
            return;
        }

        if (! in_array($masterType, self::KNOWN, true)) {
            throw new MasterTypeFormatException();
        }
    }
}
