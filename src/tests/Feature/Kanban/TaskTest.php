<?php

namespace Tests\Feature\Kanban;

use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use App\Models\KanbanTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use BuildsKanbanBoard;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBoard();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return [
            'title' => '新しいタスク',
            'description' => '説明',
            'status' => 'todo',
            'priority' => 'high',
            'assignee_id' => null,
            'due_date' => '2026-12-24',
            ...$overrides,
        ];
    }

    // ---- 作成 ----

    public function test_メンバーは列の末尾にタスクを追加でき作成者が記録される(): void
    {
        $first = $this->createTask(TaskStatus::Todo);

        $this->actingAs($this->member)
            ->post(route('projects.tasks.store', $this->project->id), $this->form(['assignee_id' => $this->owner->id]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.type', 'success');

        $created = KanbanTask::query()->where('title', '新しいタスク')->firstOrFail();
        $this->assertSame([$first->id, $created->id], $this->column(TaskStatus::Todo));
        $this->assertSame($this->member->id, $created->created_by);
        $this->assertSame($this->owner->id, $created->assignee_id);
        $this->assertSame('2026-12-24', $created->due_date?->format('Y-m-d'));
        $this->assertSame(1, $this->boardVersion());
    }

    public function test_入力が正しくなければ追加できない(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.tasks.store', $this->project->id), $this->form([
                'title' => '',
                'status' => 'unknown',
                'priority' => 'urgent',
                'due_date' => '2026/12/24',
            ]))
            ->assertSessionHasErrors([
                'title' => 'タイトルを入力してください。',
                'status' => 'ステータスの値が正しくありません。',
                'priority' => '優先度の値が正しくありません。',
                'due_date' => '期限は YYYY-MM-DD の形式で入力してください。',
            ]);

        $this->assertDatabaseCount('kanban_tasks', 0);
    }

    public function test_メンバーでない人は担当者にできない(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.tasks.store', $this->project->id), $this->form(['assignee_id' => $this->outsider->id]))
            ->assertSessionHasErrors(['assignee_id' => '担当者はプロジェクトのメンバーから選んでください。']);

        $this->assertDatabaseCount('kanban_tasks', 0);
        $this->assertSame(0, $this->boardVersion());
    }

    public function test_メンバー以外はタスクを追加できない(): void
    {
        $this->actingAs($this->outsider)
            ->post(route('projects.tasks.store', $this->project->id), $this->form())
            ->assertForbidden();
    }

    // ---- 編集 ----

    public function test_内容を編集でき並びは変わらない(): void
    {
        $a = $this->createTask(TaskStatus::Todo);
        $b = $this->createTask(TaskStatus::Todo);

        $this->actingAs($this->member)
            ->put(route('tasks.update', $a->id), $this->form(['title' => '変更後', 'assignee_id' => $this->member->id, 'due_date' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kanban_tasks', [
            'id' => $a->id,
            'title' => '変更後',
            'priority' => 'high',
            'assignee_id' => $this->member->id,
            'due_date' => null,
        ]);
        $this->assertSame([$a->id, $b->id], $this->column(TaskStatus::Todo));
        $this->assertSame(1, $this->boardVersion());
    }

    public function test_ステータスを変えると新しい列の末尾へ移り元の列は詰まる(): void
    {
        $a = $this->createTask(TaskStatus::Todo);
        $b = $this->createTask(TaskStatus::Todo);
        $done = $this->createTask(TaskStatus::Done);

        $this->actingAs($this->member)
            ->put(route('tasks.update', $a->id), $this->form(['status' => 'done']))
            ->assertSessionHasNoErrors();

        $this->assertSame([$b->id], $this->column(TaskStatus::Todo));
        $this->assertSame([$done->id, $a->id], $this->column(TaskStatus::Done));
    }

    public function test_編集でもメンバーでない人は担当者にできない(): void
    {
        $task = $this->createTask();

        $this->actingAs($this->owner)
            ->put(route('tasks.update', $task->id), $this->form(['assignee_id' => $this->outsider->id]))
            ->assertSessionHasErrors('assignee_id');

        $this->assertDatabaseHas('kanban_tasks', ['id' => $task->id, 'title' => $task->title]);
    }

    public function test_別のプロジェクトのタスクは編集できない(): void
    {
        $other = KanbanProject::factory()->ownedBy($this->outsider)->create();
        $othersTask = $this->createTask(TaskStatus::Todo, [], $other);

        $this->actingAs($this->member)
            ->put(route('tasks.update', $othersTask->id), $this->form())
            ->assertForbidden();

        $this->assertDatabaseHas('kanban_tasks', ['id' => $othersTask->id, 'title' => $othersTask->title]);
    }

    public function test_存在しないタスクは404(): void
    {
        $this->actingAs($this->owner)->put(route('tasks.update', 999999), $this->form())->assertNotFound();
        $this->actingAs($this->owner)->delete(route('tasks.destroy', 999999))->assertNotFound();
    }

    // ---- 削除 ----

    public function test_オーナーは誰が作ったタスクでも削除でき残りは詰まる(): void
    {
        $a = $this->createTask(TaskStatus::Todo, ['created_by' => $this->member->id]);
        $b = $this->createTask(TaskStatus::Todo);
        $c = $this->createTask(TaskStatus::Todo, ['created_by' => null]);

        $this->actingAs($this->owner)->delete(route('tasks.destroy', $a->id))->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->delete(route('tasks.destroy', $c->id))->assertSessionHasNoErrors();

        $this->assertSame([$b->id], $this->column(TaskStatus::Todo));
        $this->assertSame(2, $this->boardVersion());
    }

    public function test_メンバーは自分が作ったタスクだけ削除できる(): void
    {
        $own = $this->createTask(TaskStatus::Todo, ['created_by' => $this->member->id]);
        $others = $this->createTask(TaskStatus::Todo, ['created_by' => $this->owner->id]);
        $orphan = $this->createTask(TaskStatus::Todo, ['created_by' => null]);

        $this->actingAs($this->member)->delete(route('tasks.destroy', $others->id))->assertForbidden();
        $this->actingAs($this->member)->delete(route('tasks.destroy', $orphan->id))->assertForbidden();
        $this->actingAs($this->member)->delete(route('tasks.destroy', $own->id))->assertSessionHasNoErrors();

        $this->assertSame([$others->id, $orphan->id], $this->column(TaskStatus::Todo));
    }

    public function test_メンバー以外はタスクを削除できない(): void
    {
        $task = $this->createTask();

        $this->actingAs($this->outsider)->delete(route('tasks.destroy', $task->id))->assertForbidden();

        $this->assertDatabaseHas('kanban_tasks', ['id' => $task->id]);
    }
}
