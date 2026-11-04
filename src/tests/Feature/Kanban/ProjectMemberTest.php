<?php

namespace Tests\Feature\Kanban;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use BuildsKanbanBoard;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBoard();
    }

    public function test_オーナーはメールアドレスでメンバーを追加できる_大文字小文字は区別しない(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.members.store', $this->project->id), ['email' => strtoupper($this->outsider->email)])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->outsider->id,
            'role' => ProjectRole::Member->value,
        ]);
    }

    public function test_登録されていないメールアドレスは追加できない(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.members.store', $this->project->id), ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors(['email' => 'このメールアドレスのユーザーは登録されていません。']);
    }

    public function test_すでにメンバーの人は追加できない(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.members.store', $this->project->id), ['email' => $this->member->email])
            ->assertSessionHasErrors(['email' => 'このユーザーはすでにメンバーです。']);
    }

    public function test_メールアドレスの形式が正しくなければ追加できない(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.members.store', $this->project->id), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_オーナー以外はメンバーを追加できない(): void
    {
        $newcomer = User::factory()->create();

        foreach ([$this->member, $this->outsider] as $user) {
            $this->actingAs($user)
                ->post(route('projects.members.store', $this->project->id), ['email' => $newcomer->email])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('kanban_project_user', ['user_id' => $newcomer->id]);
    }

    public function test_メンバーを外すと担当していたタスクの担当者が外れボードの版が進む(): void
    {
        $assigned = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->member->id]);
        $ownersTask = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->owner->id]);

        $this->actingAs($this->owner)
            ->delete(route('projects.members.destroy', [$this->project->id, $this->member->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('kanban_project_user', ['project_id' => $this->project->id, 'user_id' => $this->member->id]);
        $this->assertDatabaseHas('kanban_tasks', ['id' => $assigned->id, 'assignee_id' => null]);
        $this->assertDatabaseHas('kanban_tasks', ['id' => $ownersTask->id, 'assignee_id' => $this->owner->id]);
        $this->assertSame(1, $this->boardVersion());
    }

    public function test_別のプロジェクトの担当は外れない(): void
    {
        $other = KanbanProject::factory()->ownedBy($this->owner)->withMember($this->member)->create();
        $otherTask = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->member->id], $other);

        $this->actingAs($this->owner)
            ->delete(route('projects.members.destroy', [$this->project->id, $this->member->id]));

        $this->assertDatabaseHas('kanban_tasks', ['id' => $otherTask->id, 'assignee_id' => $this->member->id]);
        $this->assertDatabaseHas('kanban_project_user', ['project_id' => $other->id, 'user_id' => $this->member->id]);
    }

    public function test_オーナーは外せない(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('projects.members.destroy', [$this->project->id, $this->owner->id]))
            ->assertSessionHasErrors(['member' => 'オーナーはプロジェクトから外せません。']);

        $this->assertDatabaseHas('kanban_project_user', ['project_id' => $this->project->id, 'user_id' => $this->owner->id]);
    }

    public function test_メンバーでない人を外そうとすると404(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('projects.members.destroy', [$this->project->id, $this->outsider->id]))
            ->assertNotFound();
    }

    public function test_別のプロジェクトのメンバーを横から外せない(): void
    {
        // 部外者が自分のプロジェクトを持っていても、そのプロジェクトの URL で他プロジェクトのメンバーは外せない
        $outsidersProject = KanbanProject::factory()->ownedBy($this->outsider)->create();

        $this->actingAs($this->outsider)
            ->delete(route('projects.members.destroy', [$outsidersProject->id, $this->member->id]))
            ->assertNotFound();

        $this->actingAs($this->outsider)
            ->delete(route('projects.members.destroy', [$this->project->id, $this->member->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('kanban_project_user', ['project_id' => $this->project->id, 'user_id' => $this->member->id]);
    }

    public function test_メンバーは他のメンバーを外せない(): void
    {
        $another = User::factory()->create();
        $this->project->members()->create(['user_id' => $another->id, 'role' => ProjectRole::Member->value]);

        $this->actingAs($this->member)
            ->delete(route('projects.members.destroy', [$this->project->id, $another->id]))
            ->assertForbidden();
    }
}
