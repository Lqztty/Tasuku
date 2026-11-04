<?php

namespace Tests\Integration;

use App\Domain\Project\ProjectRepositoryInterface;
use App\Models\KanbanProject;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * プロジェクト単位の排他制御が、MySQL で実際に効いていることを確かめる。
 *
 * 1本目の接続でプロジェクト行をロックしている間に、2本目の接続から同じ行をロックしに行くと
 * 待たされる（ここでは待ち時間を 1 秒にして、タイムアウトのエラーで確かめる）。
 */
class ProjectLockTest extends IntegrationTestCase
{
    private const string SECOND = 'mysql_second';

    protected function setUp(): void
    {
        parent::setUp();

        // 同じ DB に、別のセッションとしてつなぐ2本目の接続
        config(['database.connections.'.self::SECOND => config('database.connections.mysql')]);
        DB::connection(self::SECOND)->statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        DB::disconnect(self::SECOND);

        parent::tearDown();
    }

    public function test_lock_for_update_は_select_for_update_を発行する(): void
    {
        $project = KanbanProject::factory()->create();

        DB::enableQueryLog();
        DB::transaction(fn () => app(ProjectRepositoryInterface::class)->lockForUpdate($project->id));

        $sql = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        $this->assertStringContainsStringIgnoringCase('for update', $sql);
    }

    public function test_ロック中は別の接続から同じプロジェクトをロックできない(): void
    {
        $project = KanbanProject::factory()->create();

        DB::beginTransaction();
        try {
            app(ProjectRepositoryInterface::class)->lockForUpdate($project->id);

            try {
                $this->lockFromSecondConnection($project->id);
                $this->fail('ロック中なのに、別の接続からロックできてしまった。');
            } catch (QueryException $e) {
                // 1205 = Lock wait timeout exceeded
                $this->assertSame(1205, $e->errorInfo[1] ?? null, $e->getMessage());
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_別のプロジェクトはロック中でもロックできる(): void
    {
        $locked = KanbanProject::factory()->create();
        $other = KanbanProject::factory()->create();

        DB::beginTransaction();
        try {
            app(ProjectRepositoryInterface::class)->lockForUpdate($locked->id);

            $this->lockFromSecondConnection($other->id);
            $this->addToAssertionCount(1);
        } finally {
            DB::rollBack();
        }
    }

    public function test_ロックせずに読むだけなら別の接続はロックできる(): void
    {
        $project = KanbanProject::factory()->create();

        DB::beginTransaction();
        try {
            app(ProjectRepositoryInterface::class)->find($project->id);

            $this->lockFromSecondConnection($project->id);
            $this->addToAssertionCount(1);
        } finally {
            DB::rollBack();
        }
    }

    private function lockFromSecondConnection(int $projectId): void
    {
        $second = DB::connection(self::SECOND);
        $second->beginTransaction();
        try {
            $second->table('kanban_projects')->where('id', $projectId)->lockForUpdate()->first();
        } finally {
            $second->rollBack();
        }
    }
}
