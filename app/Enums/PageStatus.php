<?php

namespace App\Enums;

/**
 * ADR-055: enum phụ chưa chốt — cột DB là varchar(20), ràng buộc giá trị ở tầng ứng dụng qua
 * backed enum này, không dùng DB enum().
 */
enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Hidden = 'hidden';
}
