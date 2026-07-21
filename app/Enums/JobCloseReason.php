<?php

namespace App\Enums;

/**
 * ADR-055: enum phụ chưa chốt — cột DB là varchar(30), ràng buộc giá trị ở tầng ứng dụng qua
 * backed enum này, không dùng DB enum().
 */
enum JobCloseReason: string
{
    case RecruitmentFilled = 'recruitment_filled';
    case RecruitmentStopped = 'recruitment_stopped';
    case Expired = 'expired';
    case CompanyRequest = 'company_request';
    case Duplicate = 'duplicate';
    case Other = 'other';
}
