<?php

namespace App\Enums;

/**
 * ADR-055/062: enum phụ chưa chốt — cột DB là varchar(20), ràng buộc giá trị ở tầng ứng dụng qua
 * backed enum này, không dùng DB enum(). Xem docs/CORE-FLOWS.md mục 6.2.2.
 */
enum CandidateDuplicateReviewStatus: string
{
    case Pending = 'pending';
    case ConfirmedSame = 'confirmed_same';
    case ConfirmedDistinct = 'confirmed_distinct';
    case Dismissed = 'dismissed';
}
