<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lỗi nghiệp vụ chấm công (ngoài khung giờ, ngoài vùng quán, quá gần lần quét
 * trước...). Controller bắt exception này để redirect kèm thông báo.
 */
class AttendanceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $level = 'error'
    ) {
        parent::__construct($message);
    }
}
