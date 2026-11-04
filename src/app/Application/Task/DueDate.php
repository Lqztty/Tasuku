<?php

namespace App\Application\Task;

use App\Domain\Shared\Exceptions\InvalidInputException;
use DateTimeImmutable;

/**
 * Command の期限（Y-m-d の文字列）を日付に変換する。
 */
final class DueDate
{
    /**
     * @throws InvalidInputException
     */
    public static function parse(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        // 先頭の「!」で時刻を 00:00:00 にそろえる
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidInputException('due_date', '期限の日付が正しくありません。');
        }

        return $date;
    }
}
