<?php

namespace Tests\Integration;

use App\Application\Task\Commands\MoveTaskCommand;
use App\Application\Task\MoveTaskUseCase;
use App\Domain\Shared\Exceptions\ConflictException;
use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use App\Models\KanbanTask;

/**
 * MySQL 上で、移動の並び替えと版の更新が1つのトランザクションとして正しく行われること。
 */
class MoveTaskIntegrationTest extends IntegrationTestCase
{
    public function test_移動すると両方の列が連番に振り直され版が進む(): void
    {
        [, $member, $project] = $this->ownerMemberAndProject();
        [$a, $b, $c] = $this->tasks($project, TaskStatus::Todo, 3);
        [$x] = $this->tasks($project, TaskStatus::Done, 1);

        $this->move($member->id, $b->id, TaskStatus::Done, 0, 0);

        $this->assertColumn($project, TaskStatus::Todo, [$a->id, $c->id]);
        $this->assertColumn($project, TaskStatus::Done, [$b->id, $x->id]);
        $this->assertSame(1, $project->fresh()?->board_version);
    }

    public function test_古い版から移動すると何も変わらず版も進まない(): void
    {
        [, $member, $project] = $this->ownerMemberAndProject();
        [$a, $b] = $this->tasks($project, TaskStatus::Todo, 2);
        $project->update(['board_version' => 3]);

        try {
            $this->move($member->id, $a->id, TaskStatus::Done, 0, 2);
            $this->fail('ConflictException が投げられなかった。');
        } catch (ConflictException) {
            // 期待どおり
        }

        $this->assertColumn($project, TaskStatus::Todo, [$a->id, $b->id]);
        $this->assertColumn($project, TaskStatus::Done, []);
        $this->assertSame(3, $project->fresh()?->board_version);
    }

    private function move(int $userId, int $taskId, TaskStatus $status, int $position, int $expectedVersion): void
    {
        app(MoveTaskUseCase::class)->execute(new MoveTaskCommand(
            userId: $userId,
            taskId: $taskId,
            status: $status->value,
            position: $position,
            expectedVersion: $expectedVersion,
        ));
    }

    /**
     * @return list<KanbanTask>
     */
    private function tasks(KanbanProject $project, TaskStatus $status, int $count): array
    {
        return array_map(
            fn (int $position): KanbanTask => KanbanTask::factory()->create([
                'project_id' => $project->id,
                'status' => $status->value,
                'position' => $position,
            ]),
            range(0, $count - 1),
        );
    }

    /**
     * @param  list<int>  $expectedIds
     */
    private function assertColumn(KanbanProject $project, TaskStatus $status, array $expectedIds): void
    {
        $rows = KanbanTask::query()
            ->where('project_id', $project->id)
            ->where('status', $status->value)
            ->orderBy('position')
            ->get(['id', 'position']);

        $this->assertSame($expectedIds, $rows->pluck('id')->all());
        $this->assertSame(array_keys($expectedIds), $rows->pluck('position')->all());
    }
}
