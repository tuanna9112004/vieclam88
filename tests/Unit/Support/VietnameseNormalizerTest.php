<?php

namespace Tests\Unit\Support;

use App\Support\VietnameseNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Full Name Normalization Contract — docs/CORE-FLOWS.md muc 6.2 (ADR-063).
 */
class VietnameseNormalizerTest extends TestCase
{
    public function test_trims_leading_and_trailing_whitespace(): void
    {
        $this->assertSame('nguyễn văn a', VietnameseNormalizer::normalize('  Nguyễn Văn A  '));
    }

    public function test_collapses_repeated_whitespace(): void
    {
        $this->assertSame('nguyễn văn a', VietnameseNormalizer::normalize('Nguyễn   Văn    A'));
    }

    public function test_lowercases_while_keeping_vietnamese_diacritics(): void
    {
        $this->assertSame('trần thị bích', VietnameseNormalizer::normalize('TRẦN THỊ BÍCH'));
    }

    public function test_strips_punctuation(): void
    {
        $this->assertSame('nguyễn văn a', VietnameseNormalizer::normalize('Nguyễn, Văn. A!!'));
    }

    public function test_keeps_digits(): void
    {
        $this->assertSame('nguyễn văn a 2', VietnameseNormalizer::normalize('Nguyễn Văn A 2'));
    }

    public function test_result_is_idempotent(): void
    {
        $once = VietnameseNormalizer::normalize('  Lê  Thị,  C.  ');
        $twice = VietnameseNormalizer::normalize($once);

        $this->assertSame($once, $twice);
        $this->assertSame('lê thị c', $twice);
    }

    public function test_empty_value_returns_empty_string(): void
    {
        $this->assertSame('', VietnameseNormalizer::normalize(''));
        $this->assertSame('', VietnameseNormalizer::normalize('   '));
    }
}
