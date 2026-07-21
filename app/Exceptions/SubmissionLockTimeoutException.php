<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * ADR-061 buoc 3: khong giu duoc GET_LOCK trong timeout — caller phai tra loi than thien
 * ("vui long thu lai sau vai giay"), khong phai loi 500.
 */
class SubmissionLockTimeoutException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Hệ thống đang xử lý một yêu cầu khác cho số điện thoại này, vui lòng thử lại sau vài giây.');
    }
}
