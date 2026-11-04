<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

/**
 * 他の人が先に更新していたため、操作を適用しなかった。
 *
 * HTTP ではボード画面へ戻し、トーストで知らせる（bootstrap/app.php）。
 * 戻った先で最新の状態が表示されるので、画面はサーバーの状態に揃う。
 */
final class ConflictException extends DomainException
{
    public function __construct(string $message = '他のメンバーが先に更新したため、最新の状態を表示しています。')
    {
        parent::__construct($message);
    }
}
