<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

/**
 * 対象が存在しない。HTTP では 404 に変換する（bootstrap/app.php）。
 */
final class NotFoundException extends DomainException
{
    public function __construct(string $message = '対象が見つかりません。')
    {
        parent::__construct($message);
    }
}
