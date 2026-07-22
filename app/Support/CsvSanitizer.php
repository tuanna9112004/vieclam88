<?php

namespace App\Support;

/**
 * Phòng chống CSV Formula Injection (ADR-019, docs/PHASE-1-SCOPE.md).
 * Escape các ô dữ liệu bắt đầu bằng =, +, -, @, \t, \r để tránh thực thi mã độc trong Excel.
 */
class CsvSanitizer
{
    public static function escape(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = ltrim($value);

        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
