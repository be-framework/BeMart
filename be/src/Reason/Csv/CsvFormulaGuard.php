<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Csv;

use function in_array;
use function is_numeric;
use function substr;

/**
 * CSV formula-injection guard — the single neutralisation point for every
 * BeMart CSV write boundary ({@see CsvColumnLayout::project()}, the
 * category export and the EC-CUBE class CSV encoder).
 *
 * Stored values are exported verbatim, so a cell whose first character is
 * one of `=` `+` `-` `@` TAB CR is evaluated as a formula when the admin
 * opens the download in Excel / LibreOffice / Google Sheets. That turns
 * any anonymously-writable field that reaches an export (dtb_customer
 * name01 via `POST /entry`, for example) into remote code execution on
 * the admin's machine. Validation cannot fix this — the stored value must
 * stay exactly what the customer typed — so the escape belongs here, at
 * the boundary.
 *
 * A leading apostrophe is the spreadsheet's own "this cell is text"
 * marker, so the guarded cell displays and re-imports as the original
 * string instead of a formula.
 */
final class CsvFormulaGuard
{
    /** First characters a spreadsheet reads as the start of a formula. */
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Numeric cells pass through untouched: a real negative number such
     * as `-1200` starts with a trigger character but must still import
     * as a number, and no spreadsheet evaluates it as a formula.
     */
    public static function neutralize(string|int $cell): string|int
    {
        if (is_numeric($cell)) {
            return $cell;
        }

        if (! in_array(substr($cell, 0, 1), self::TRIGGERS, true)) {
            return $cell;
        }

        return "'" . $cell;
    }
}
