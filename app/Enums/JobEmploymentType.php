<?php

namespace App\Enums;

/**
 * ADR-055: enum phụ chưa chốt — cột DB là varchar(20), ràng buộc giá trị ở tầng ứng dụng qua
 * backed enum này, không dùng DB enum().
 */
enum JobEmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Seasonal = 'seasonal';
    case Temporary = 'temporary';
}
