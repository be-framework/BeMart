<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\BaseInfoFetched;

/**
 * Input for goBaseInfo — admin views the shop base info form (Wave 9).
 *
 *   GetBaseInfoInput → BaseInfoFetched  (Direct, safe read, admin AUTHZ)
 *
 * Pair of the Wave 8ε {@see UpdateBaseInfoInput} write side. Same
 * single-row `dtb_base_info` projection — this Input simply pulls the
 * current row for the admin form to render. No filter / paging because
 * dtb_base_info is a single-row table.
 *
 * AUTHZ in the Final (AdminSession); the customer-side
 * BaseInfo display surface lives behind a separate help page (Wave 3H
 * `goHelpAbout`).
 *
 * @link https://schema.org/ReadAction
 */
#[Be(BaseInfoFetched::class)]
final readonly class GetBaseInfoInput
{
    public function __construct()
    {
    }
}
