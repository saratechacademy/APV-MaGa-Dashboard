<?php

namespace App\Support;

class SafeExport
{
    /**
     * Neutralize CSV/Excel formula injection: any cell whose text starts with
     * =, +, -, @, tab or CR is prefixed with a leading apostrophe so
     * spreadsheet apps treat it as a literal string instead of a formula.
     */
    public static function cell(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    /**
     * Apply cell() to every value in a flat row array.
     */
    public static function row(array $row): array
    {
        return array_map([self::class, 'cell'], $row);
    }
}
