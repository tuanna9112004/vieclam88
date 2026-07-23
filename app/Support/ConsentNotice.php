<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Nguon server-side duy nhat cho noi dung + version consent o form ung tuyen public
 * (docs/CORE-FLOWS.md muc 7.3, applications.consent_version/consent_text_hash). View va
 * CreateApplicationAction deu phai doc tu day — khong duoc nhan version/text tu client, khong
 * duoc tu suy noi dung o noi khac de tranh lech giua text hien thi va hash da luu.
 */
class ConsentNotice
{
    public const string CURRENT_VERSION = 'v1';

    /** @var array<string, string> */
    private const array TEXTS = [
        'v1' => 'Tôi đồng ý cho phép thu thập và sử dụng thông tin trên để công ty liên hệ tư vấn việc làm phù hợp.',
    ];

    public static function currentVersion(): string
    {
        return self::CURRENT_VERSION;
    }

    public static function currentText(): string
    {
        return self::textFor(self::CURRENT_VERSION);
    }

    public static function textFor(string $version): string
    {
        if (! isset(self::TEXTS[$version])) {
            throw new InvalidArgumentException("Unknown consent version: {$version}");
        }

        return self::TEXTS[$version];
    }

    /**
     * Chuan hoa xuong dong va khoang trang thua truoc khi hash, de hash on dinh giua cac moi
     * truong (Windows/Linux CRLF vs LF) nhung van phan anh dung noi dung da hien thi.
     */
    public static function normalize(string $text): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $text));
    }

    public static function hashFor(string $version): string
    {
        return hash('sha256', self::normalize(self::textFor($version)));
    }

    public static function currentHash(): string
    {
        return self::hashFor(self::CURRENT_VERSION);
    }
}
