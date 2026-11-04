<?php

namespace Tests\Integration;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Models\KanbanProject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * MySQL（tasuku_testing）を使う結合テストの基底クラス。
 *
 * RefreshDatabase はテスト全体を1つのトランザクションで包むため、
 * 他の接続からはデータが見えず、ロックの検証ができない。
 * DatabaseTruncation でデータを実際にコミットし、テストごとにテーブルを空にする。
 */
abstract class IntegrationTestCase extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->fail('結合テストは MySQL で実行してください（--configuration=phpunit.integration.xml）。');
        }
    }

    /**
     * @return array{User, User, KanbanProject}
     */
    protected function ownerMemberAndProject(): array
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = KanbanProject::factory()->ownedBy($owner)->withMember($member)->create();

        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectRole::Owner->value,
        ]);

        return [$owner, $member, $project];
    }
}
