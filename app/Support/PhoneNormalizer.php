<?php

namespace App\Support;

/**
 * Chuẩn hóa số điện thoại VN về dạng nội địa `0xxxxxxxxx`: bỏ khoảng trắng/dấu gạch/ngoặc, quy
 * đổi tiền tố quốc tế `+84`/`84` về `0`. Chưa có ADR chính thức cho thuật toán này (khác
 * `full_name_normalized` — ADR-063); đây là quy ước kỹ thuật xác định (không phải chính sách dữ
 * liệu cá nhân), dùng chung cho mọi nơi cần `phone_normalized`/`normalized_value`.
 */
class PhoneNormalizer
{
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/[^\d+]/', '', trim($value));

        if (str_starts_with($digits, '+84')) {
            $digits = '0'.substr($digits, 3);
        } elseif (str_starts_with($digits, '84') && strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_replace('/\D/', '', $digits);
    }
}
