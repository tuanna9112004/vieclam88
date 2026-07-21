<?php

namespace App\Support;

use Normalizer;

/**
 * Full Name Normalization Contract — docs/CORE-FLOWS.md muc 6.2 (ADR-063).
 */
class VietnameseNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = Normalizer::normalize($value, Normalizer::FORM_C);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', '', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }
}
