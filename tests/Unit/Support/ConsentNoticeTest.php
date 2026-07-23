<?php

namespace Tests\Unit\Support;

use App\Support\ConsentNotice;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConsentNoticeTest extends TestCase
{
    public function test_current_hash_matches_sha256_of_normalized_current_text(): void
    {
        $expected = hash('sha256', trim(ConsentNotice::currentText()));

        $this->assertSame($expected, ConsentNotice::currentHash());
    }

    public function test_normalize_trims_and_unifies_line_endings_before_hashing(): void
    {
        $raw = "  Nội dung\r\ncó xuống dòng  ";
        $normalized = ConsentNotice::normalize($raw);

        $this->assertSame("Nội dung\ncó xuống dòng", $normalized);
    }

    public function test_different_text_produces_different_hash(): void
    {
        $textA = ConsentNotice::normalize('Phiên bản A');
        $textB = ConsentNotice::normalize('Phiên bản B');

        $this->assertNotSame(hash('sha256', $textA), hash('sha256', $textB));
    }

    public function test_hash_for_current_version_is_stable_across_calls(): void
    {
        $this->assertSame(
            ConsentNotice::hashFor(ConsentNotice::currentVersion()),
            ConsentNotice::hashFor(ConsentNotice::currentVersion())
        );
    }

    /**
     * Hop dong version/text: mot version khong co noi dung publish phai bi phat hien ngay (khong
     * duoc am tham tra ve chuoi rong hay hash cua gia tri null).
     */
    public function test_unknown_version_throws_instead_of_silently_hashing_empty_text(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConsentNotice::textFor('v999-does-not-exist');
    }

    public function test_current_version_resolves_to_a_published_text(): void
    {
        $this->assertSame(
            ConsentNotice::textFor(ConsentNotice::currentVersion()),
            ConsentNotice::currentText()
        );
    }
}
