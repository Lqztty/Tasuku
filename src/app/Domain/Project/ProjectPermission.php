<?php

namespace App\Domain\Project;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\ForbiddenException;

/**
 * プロジェクト内の操作権限の判定。
 *
 * ロールが null のときは「メンバーではない」ことを表す。
 * 判定の表は計画書・CLAUDE.md の「権限」の表と一致させること。
 *
 * | 操作                         | オーナー | メンバー             | メンバー以外 |
 * | ---------------------------- | -------- | -------------------- | ------------ |
 * | ボードの閲覧                 | ○        | ○                    | ×            |
 * | プロジェクトの編集・削除     | ○        | ×                    | ×            |
 * | メンバーの追加・削除         | ○        | ×                    | ×            |
 * | タスクの作成・編集・移動     | ○        | ○                    | ×            |
 * | タスクの削除                 | すべて   | 自分が作成したものだけ | ×          |
 */
final class ProjectPermission
{
    public static function canView(?ProjectRole $role): bool
    {
        return $role !== null;
    }

    /**
     * プロジェクトの編集・削除と、メンバーの追加・削除。
     */
    public static function canManageProject(?ProjectRole $role): bool
    {
        return $role === ProjectRole::Owner;
    }

    /**
     * タスクの作成・編集・移動。
     */
    public static function canEditTasks(?ProjectRole $role): bool
    {
        return $role !== null;
    }

    /**
     * タスクの削除。作成者が退会して null になったタスクは、オーナーだけが削除できる。
     */
    public static function canDeleteTask(?ProjectRole $role, int $userId, ?int $createdBy): bool
    {
        return match ($role) {
            ProjectRole::Owner => true,
            ProjectRole::Member => $createdBy !== null && $createdBy === $userId,
            null => false,
        };
    }

    /**
     * @throws ForbiddenException
     */
    public static function ensureCanView(?ProjectRole $role): void
    {
        self::ensure(self::canView($role));
    }

    /**
     * @throws ForbiddenException
     */
    public static function ensureCanManageProject(?ProjectRole $role): void
    {
        self::ensure(self::canManageProject($role));
    }

    /**
     * @throws ForbiddenException
     */
    public static function ensureCanEditTasks(?ProjectRole $role): void
    {
        self::ensure(self::canEditTasks($role));
    }

    /**
     * @throws ForbiddenException
     */
    public static function ensureCanDeleteTask(?ProjectRole $role, int $userId, ?int $createdBy): void
    {
        self::ensure(self::canDeleteTask($role, $userId, $createdBy));
    }

    private static function ensure(bool $allowed): void
    {
        if (! $allowed) {
            throw new ForbiddenException;
        }
    }
}
