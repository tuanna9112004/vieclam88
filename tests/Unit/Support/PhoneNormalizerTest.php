<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_local_format_is_unchanged(): void
    {
        $this->assertSame('0987654321', PhoneNormalizer::normalize('0987654321'));
    }

    public function test_international_plus_prefix_is_converted_to_local(): void
    {
        $this->assertSame('0987654321', PhoneNormalizer::normalize('+84987654321'));
    }

    public function test_international_prefix_without_plus_is_converted_to_local(): void
    {
        $this->assertSame('0987654321', PhoneNormalizer::normalize('84987654321'));
    }

    public function test_spaces_dashes_and_parentheses_are_stripped(): void
    {
        $this->assertSame('0987654321', PhoneNormalizer::normalize('0987 654 321'));
        $this->assertSame('0987654321', PhoneNormalizer::normalize('0987-654-321'));
        $this->assertSame('0987654321', PhoneNormalizer::normalize('(0987) 654321'));
    }

    public function test_result_is_idempotent(): void
    {
        $once = PhoneNormalizer::normalize('+84 987 654 321');
        $twice = PhoneNormalizer::normalize($once);

        $this->assertSame($once, $twice);
        $this->assertSame('0987654321', $twice);
    }

    public function test_short_ambiguous_prefix_is_not_misinterpreted_as_country_code(): void
    {
        // '84' don le (chi 2 chu so) khong du dai de coi la ma quoc gia — giu nguyen.
        $this->assertSame('84', PhoneNormalizer::normalize('84'));
    }

    public function test_empty_value_returns_empty_string_without_exception(): void
    {
        $this->assertSame('', PhoneNormalizer::normalize(''));
        $this->assertSame('', PhoneNormalizer::normalize('   '));
    }

    public function test_local_landline_with_area_code_is_kept_digits_only(): void
    {
        $this->assertSame('02871234567', PhoneNormalizer::normalize('028.7123.4567'));
    }
}
