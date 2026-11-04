<?php

namespace Tests\Unit\Domain\Project;

use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\ForbiddenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 権限の表（ProjectPermission の docblock）をすべて確かめる。
 */
class ProjectPermissionTest extends TestCase
{
    private const int USER_ID = 10;

    private const int OTHER_USER_ID = 20;

    /**
     * @return array<string, array{?ProjectRole, bool, bool, bool}>
     */
    public static function roles(): array
    {
        // [ロール, 閲覧, プロジェクト管理, タスク編集]
        return [
            'オーナー' => [ProjectRole::Owner, true, true, true],
            'メンバー' => [ProjectRole::Member, true, false, true],
            'メンバー以外' => [null, false, false, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_ロールごとの権限(?ProjectRole $role, bool $view, bool $manage, bool $editTasks): void
    {
        $this->assertSame($view, ProjectPermission::canView($role));
        $this->assertSame($manage, ProjectPermission::canManageProject($role));
        $this->assertSame($editTasks, ProjectPermission::canEditTasks($role));
    }

    /**
     * @return array<string, array{?ProjectRole, ?int, bool}>
     */
    public static function taskDeletions(): array
    {
        // [ロール, タスクの作成者, 削除できるか]
        return [
            'オーナーは自分のタスクを削除できる' => [ProjectRole::Owner, self::USER_ID, true],
            'オーナーは他人のタスクも削除できる' => [ProjectRole::Owner, self::OTHER_USER_ID, true],
            'オーナーは作成者が退会したタスクも削除できる' => [ProjectRole::Owner, null, true],
            'メンバーは自分のタスクを削除できる' => [ProjectRole::Member, self::USER_ID, true],
            'メンバーは他人のタスクを削除できない' => [ProjectRole::Member, self::OTHER_USER_ID, false],
            'メンバーは作成者が退会したタスクを削除できない' => [ProjectRole::Member, null, false],
            'メンバー以外は削除できない' => [null, self::USER_ID, false],
        ];
    }

    #[DataProvider('taskDeletions')]
    public function test_タスクの削除権限(?ProjectRole $role, ?int $createdBy, bool $expected): void
    {
        $this->assertSame($expected, ProjectPermission::canDeleteTask($role, self::USER_ID, $createdBy));
    }

    public function test_権限がなければ_ensure_は_forbidden_を投げる(): void
    {
        $this->expectException(ForbiddenException::class);

        ProjectPermission::ensureCanManageProject(ProjectRole::Member);
    }

    public function test_権限があれば_ensure_は何もしない(): void
    {
        ProjectPermission::ensureCanEditTasks(ProjectRole::Member);

        $this->addToAssertionCount(1);
    }
}
