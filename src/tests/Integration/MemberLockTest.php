<?php

namespace Tests\Integration;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * オーナーの引き継ぎと退会が同時に走っても「オーナーのいないプロジェクト」ができないよう、
 * 両方がメンバー行をロックして読んでいることを MySQL で確かめる。
 *
 * 1本目の接続でメンバー行をロックしている間に、2本目の接続から同じ行をロックしに行くと
 * 待たされる（待ち時間を 1 秒にして、タイムアウトのエラーで確かめる）。
 */
class MemberLockTest extends IntegrationTestCase
{
    private const string SECOND = 'mysql_second';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::SECOND => config('database.connections.mysql')]);
        DB::connection(self::SECOND)->statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        DB::disconnect(self::SECOND);

        parent::tearDown();
    }

    public function test_ロック付きの読み取りは_select_for_update_を発行する(): void
    {
        [, $member, $project] = $this->ownerMemberAndProject();
        $members = app(ProjectMemberRepositoryInterface::class);

        DB::enableQueryLog();
        $role = DB::transaction(fn () => $members->findRoleForUpdate($project->id, $member->id));
        $roles = DB::transaction(fn () => $members->findRolesByUserForUpdate($member->id));

        $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql) => str_contains($sql, 'kanban_project_user'));
        $this->assertCount(2, $queries);
        foreach ($queries as $sql) {
            $this->assertStringContainsStringIgnoringCase('for update', $sql);
        }
        $this->assertSame(ProjectRole::Member, $role);
        $this->assertSame([ProjectRole::Member], $roles);
    }

    public function test_退会の確認中は別の接続から引き継ぎの相手としてロックできない(): void
    {
        [, $member, $project] = $this->ownerMemberAndProject();

        DB::beginTransaction();
        try {
            app(ProjectMemberRepositoryInterface::class)->findRolesByUserForUpdate($member->id);

            $this->assertLockWaitTimeout(fn () => $this->lockMemberRowFromSecondConnection($project->id, $member->id));
        } finally {
            DB::rollBack();
        }
    }

    public function test_引き継ぎの途中は別の接続から退会の確認ができない(): void
    {
        [, $member, $project] = $this->ownerMemberAndProject();

        DB::beginTransaction();
        try {
            app(ProjectMemberRepositoryInterface::class)->findRoleForUpdate($project->id, $member->id);

            $this->assertLockWaitTimeout(function () use ($member): void {
                $second = DB::connection(self::SECOND);
                $second->beginTransaction();
                try {
                    $second->table('kanban_project_user')->where('user_id', $member->id)->lockForUpdate()->get();
                } finally {
                    $second->rollBack();
                }
            });
        } finally {
            DB::rollBack();
        }
    }

    public function test_別のユーザーの行はロック中でもロックできる(): void
    {
        [$owner, $member, $project] = $this->ownerMemberAndProject();

        DB::beginTransaction();
        try {
            app(ProjectMemberRepositoryInterface::class)->findRolesByUserForUpdate($member->id);

            $this->lockMemberRowFromSecondConnection($project->id, $owner->id);
            $this->addToAssertionCount(1);
        } finally {
            DB::rollBack();
        }
    }

    private function lockMemberRowFromSecondConnection(int $projectId, int $userId): void
    {
        $second = DB::connection(self::SECOND);
        $second->beginTransaction();
        try {
            $second->table('kanban_project_user')
                ->where('project_id', $projectId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
        } finally {
            $second->rollBack();
        }
    }

    private function assertLockWaitTimeout(callable $lock): void
    {
        try {
            $lock();
            $this->fail('ロック中なのに、別の接続からロックできてしまった。');
        } catch (QueryException $e) {
            // 1205 = Lock wait timeout exceeded
            $this->assertSame(1205, $e->errorInfo[1] ?? null, $e->getMessage());
        }
    }
}
