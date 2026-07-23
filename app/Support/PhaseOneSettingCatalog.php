<?php

namespace App\Support;

/**
 * Allowlist duy nhất cho UI cấu hình Phase 1.
 *
 * Không đưa secret, credential hoặc key Phase 2 vào đây. Request và Action cùng đọc catalog
 * này để client không thể đổi kiểu dữ liệu hoặc chèn thêm key ngoài phạm vi.
 */
final class PhaseOneSettingCatalog
{
    /**
     * @var array<string, array{
     *     label: string,
     *     type: 'integer'|'boolean',
     *     default: int|bool|null,
     *     nullable: bool,
     *     help: string
     * }>
     */
    public const array DEFINITIONS = [
        'job_verification_warning_days' => [
            'label' => 'Số ngày trước khi cảnh báo xác minh',
            'type' => 'integer',
            'default' => 7,
            'nullable' => false,
            'help' => 'Job đang xuất bản sẽ hiện cảnh báo thường sau số ngày này.',
        ],
        'job_auto_pause_days' => [
            'label' => 'Số ngày cảnh báo mức cao',
            'type' => 'integer',
            'default' => 14,
            'nullable' => false,
            'help' => 'Phải lớn hơn số ngày cảnh báo xác minh.',
        ],
        'job_auto_pause_enabled' => [
            'label' => 'Bật cơ chế tự động tạm dừng',
            'type' => 'boolean',
            'default' => false,
            'nullable' => false,
            'help' => 'Phase 1 mặc định tắt; chỉ lưu lựa chọn cấu hình đã chốt.',
        ],
        'job_verification_valid_days' => [
            'label' => 'Hiệu lực của lần xác minh (ngày)',
            'type' => 'integer',
            'default' => null,
            'nullable' => true,
            'help' => 'Để trống để không giới hạn độ mới của lần xác minh.',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::DEFINITIONS);
    }
}
