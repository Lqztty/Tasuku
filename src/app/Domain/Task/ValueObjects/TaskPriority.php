<?php

namespace App\Domain\Task\ValueObjects;

/**
 * タスクの優先度。
 */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
