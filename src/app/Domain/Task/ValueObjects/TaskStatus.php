<?php

namespace App\Domain\Task\ValueObjects;

/**
 * タスクのステータス（＝かんばんの列）。
 *
 * 列は固定の4つ。並び順は cases() の順番どおり（ボードの左から右）。
 */
enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';
}
