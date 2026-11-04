<?php

namespace Tests\Feature\Kanban;

use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * マイタスク（dashboard）。
 */
class MyTaskTest extends TestCase
{
    use BuildsKanbanBoard;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBoard();
    }

    public function test_自分が担当するタスクだけをプロジェクトをまたいで期限の近い順に出す(): void
    {
        $other = KanbanProject::factory()->ownedBy($this->member)->create(['name' => '別プロジェクト']);

        $later = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->member->id, 'due_date' => '2026-12-31']);
        $noDue = $this->createTask(TaskStatus::Review, ['assignee_id' => $this->member->id, 'due_date' => null]);
        $sooner = $this->createTask(TaskStatus::InProgress, ['assignee_id' => $this->member->id, 'due_date' => '2026-11-10'], $other);
        $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->owner->id, 'due_date' => '2026-11-01']);
        $this->createTask(TaskStatus::Todo, ['assignee_id' => null]);

        $this->actingAs($this->member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('tasks', 3)
                ->where('tasks.0.id', $sooner->id)
                ->where('tasks.0.project_name', '別プロジェクト')
                ->where('tasks.0.status', 'in_progress')
                ->where('tasks.0.due_date', '2026-11-10')
                ->where('tasks.1.id', $later->id)
                ->where('tasks.1.project_name', $this->project->name)
                ->where('tasks.2.id', $noDue->id)
                ->where('tasks.2.due_date', null));
    }

    public function test_担当がなければ空(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')->has('tasks', 0));
    }
}
