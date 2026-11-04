<?php

namespace Tests\Feature\Kanban;

use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ドラッグ&ドロップの移動と、board_version による競合の検出。
 */
class MoveTaskTest extends TestCase
{
    use BuildsKanbanBoard;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBoard();
    }

    public function test_同じ列の中で並べ替えると連番が振り直され版が進む(): void
    {
        $a = $this->createTask(TaskStatus::Todo);
        $b = $this->createTask(TaskStatus::Todo);
        $c = $this->createTask(TaskStatus::Todo);

        $this->actingAs($this->member)
            ->from(route('projects.show', $this->project->id))
            ->patch(route('tasks.move', $a->id), ['status' => 'todo', 'position' => 2, 'expected_version' => 0])
            ->assertRedirect(route('projects.show', $this->project->id))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlashMissing('toast');

        $this->assertSame([$b->id, $c->id, $a->id], $this->column(TaskStatus::Todo));
        $this->assertSame(1, $this->boardVersion());
    }

    public function test_別の列の途中へ移動できる(): void
    {
        $a = $this->createTask(TaskStatus::Todo);
        $b = $this->createTask(TaskStatus::Todo);
        $x = $this->createTask(TaskStatus::InProgress);
        $y = $this->createTask(TaskStatus::InProgress);

        $this->actingAs($this->member)
            ->patch(route('tasks.move', $a->id), ['status' => 'in_progress', 'position' => 1, 'expected_version' => 0])
            ->assertSessionHasNoErrors();

        $this->assertSame([$b->id], $this->column(TaskStatus::Todo));
        $this->assertSame([$x->id, $a->id, $y->id], $this->column(TaskStatus::InProgress));
    }

    public function test_古い版から移動すると何も変えずに競合を知らせる(): void
    {
        $a = $this->createTask(TaskStatus::Todo);
        $b = $this->createTask(TaskStatus::Todo);
        $this->project->update(['board_version' => 5]);

        $this->actingAs($this->member)
            ->from(route('projects.show', $this->project->id))
            ->patch(route('tasks.move', $a->id), ['status' => 'done', 'position' => 0, 'expected_version' => 4])
            ->assertRedirect(route('projects.show', $this->project->id))
            ->assertInertiaFlash('toast.type', 'warning')
            ->assertInertiaFlash('toast.message', '他のメンバーが先に更新したため、最新の状態を表示しています。');

        $this->assertSame([$a->id, $b->id], $this->column(TaskStatus::Todo));
        $this->assertSame([], $this->column(TaskStatus::Done));
        $this->assertSame(5, $this->boardVersion());
    }

    public function test_続けて移動するときは前の移動で進んだ版を送れば通る(): void
    {
        $a = $this->createTask(TaskStatus::Todo);

        $this->actingAs($this->member)
            ->patch(route('tasks.move', $a->id), ['status' => 'in_progress', 'position' => 0, 'expected_version' => 0]);
        $this->actingAs($this->member)
            ->patch(route('tasks.move', $a->id), ['status' => 'done', 'position' => 0, 'expected_version' => 1])
            ->assertInertiaFlashMissing('toast');

        $this->assertSame([$a->id], $this->column(TaskStatus::Done));
        $this->assertSame(2, $this->boardVersion());
    }

    public function test_メンバー以外は版が古くても競合ではなく403(): void
    {
        $a = $this->createTask(TaskStatus::Todo);

        $this->actingAs($this->outsider)
            ->patch(route('tasks.move', $a->id), ['status' => 'done', 'position' => 0, 'expected_version' => 99])
            ->assertForbidden();

        $this->assertSame([$a->id], $this->column(TaskStatus::Todo));
    }

    public function test_別のプロジェクトのタスクは移動できない(): void
    {
        $other = KanbanProject::factory()->ownedBy($this->outsider)->create();
        $othersTask = $this->createTask(TaskStatus::Todo, [], $other);

        $this->actingAs($this->member)
            ->patch(route('tasks.move', $othersTask->id), ['status' => 'done', 'position' => 0, 'expected_version' => 0])
            ->assertForbidden();
    }

    public function test_移動先と版は必須(): void
    {
        $a = $this->createTask(TaskStatus::Todo);

        $this->actingAs($this->member)
            ->patch(route('tasks.move', $a->id), ['status' => 'archived', 'position' => -1])
            ->assertSessionHasErrors(['status', 'position', 'expected_version']);

        $this->assertSame(0, $this->boardVersion());
    }
}
