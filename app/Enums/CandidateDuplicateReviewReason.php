<?php

namespace App\Enums;

/**
 * ADR-055/062: enum phụ chưa chốt — cột DB là varchar(30), ràng buộc giá trị ở tầng ứng dụng qua
 * backed enum này, không dùng DB enum(). Xem docs/CORE-FLOWS.md mục 6.2.1.
 */
enum CandidateDuplicateReviewReason: string
{
    case SamePhoneMissingDob = 'same_phone_missing_dob';
    case SamePhoneDifferentName = 'same_phone_different_name';
    case SameIdentityConflictingDob = 'same_identity_conflicting_dob';
    case MultipleExactMatches = 'multiple_exact_matches';
    case Other = 'other';
}
