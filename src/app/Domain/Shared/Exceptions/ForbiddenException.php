<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

/**
 * 操作する権限がない。HTTP では 403 に変換する（bootstrap/app.php）。
 */
final class ForbiddenException extends DomainException
{
    public function __construct(string $message = 'この操作を行う権限がありません。')
    {
        parent::__construct($message);
    }
}
