<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

/**
 * 入力の形式は正しいが、業務ルールに合わない（例: メンバーでない人を担当者にした）。
 *
 * HTTP ではバリデーションエラー（$field の欄に $message）に変換する（bootstrap/app.php）。
 */
final class InvalidInputException extends DomainException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
