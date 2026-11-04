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

    public function test_オーナーはメンバーにオーナーを引き継げる(): void
    {
        $this->actingAs($this->owner)
            ->patch(route('projects.members.owner', [$this->project->id, $this->member->id]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->member->id,
            'role' => ProjectRole::Owner->value,
        ]);
        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'role' => ProjectRole::Member->value,
        ]);
        $this->assertSame(1, $this->project->members()->where('role', ProjectRole::Owner->value)->count());
        // タスクの並びや担当者は変わらないので、ボードの版は進めない
        $this->assertSame(0, $this->boardVersion());
    }

    public function test_引き継いだ後は新しいオーナーだけがメンバーを管理できる(): void
    {
        $newcomer = User::factory()->create();

        $this->actingAs($this->owner)
            ->patch(route('projects.members.owner', [$this->project->id, $this->member->id]));

        $this->actingAs($this->owner)
            ->post(route('projects.members.store', $this->project->id), ['email' => $newcomer->email])
            ->assertForbidden();

        $this->actingAs($this->member)
            ->post(route('projects.members.store', $this->project->id), ['email' => $newcomer->email])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kanban_project_user', ['project_id' => $this->project->id, 'user_id' => $newcomer->id]);
    }

    public function test_オーナー以外はオーナーを引き継げない(): void
    {
        $another = User::factory()->create();
        $this->project->members()->create(['user_id' => $another->id, 'role' => ProjectRole::Member->value]);

        $this->actingAs($this->member)
            ->patch(route('projects.members.owner', [$this->project->id, $another->id]))
            ->assertForbidden();

        $this->actingAs($this->outsider)
            ->patch(route('projects.members.owner', [$this->project->id, $this->member->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'role' => ProjectRole::Owner->value,
        ]);
    }

    public function test_メンバーでない人にはオーナーを引き継げない(): void
    {
        $this->actingAs($this->owner)
            ->patch(route('projects.members.owner', [$this->project->id, $this->outsider->id]))
            ->assertNotFound();

        $this->assertDatabaseMissing('kanban_project_user', ['project_id' => $this->project->id, 'user_id' => $this->outsider->id]);
    }

    public function test_自分自身にはオーナーを引き継げない(): void
    {
        $this->actingAs($this->owner)
            ->patch(route('projects.members.owner', [$this->project->id, $this->owner->id]))
            ->assertSessionHasErrors(['member' => 'すでにオーナーです。']);

        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'role' => ProjectRole::Owner->value,
        ]);
    }

    public function test_別のプロジェクトのメンバーを指定して引き継げない(): void
    {
        $outsidersProject = KanbanProject::factory()->ownedBy($this->outsider)->create();

        $this->actingAs($this->outsider)
            ->patch(route('projects.members.owner', [$outsidersProject->id, $this->member->id]))
            ->assertNotFound();

        $this->assertDatabaseMissing('kanban_project_user', ['project_id' => $outsidersProject->id, 'user_id' => $this->member->id]);
    }

    public function test_メンバーは自分から抜けられ_担当していたタスクの担当者が外れボードの版が進む(): void
    {
        $assigned = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->member->id]);
        $created = $this->createTask(TaskStatus::Todo, ['created_by' => $this->member->id]);
        $ownersTask = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->owner->id]);

        $this->actingAs($this->member)
            ->delete(route('projects.leave', $this->project->id))
            ->assertRedirect(route('projects.index'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseMissing('kanban_project_user', ['project_id' => $this->project->id, 'user_id' => $this->member->id]);
        $this->assertDatabaseHas('kanban_tasks', ['id' => $assigned->id, 'assignee_id' => null]);
        $this->assertDatabaseHas('kanban_tasks', ['id' => $ownersTask->id, 'assignee_id' => $this->owner->id]);
        // 作成者は残す（抜けた人が作ったタスクはオーナーだけが削除できる）
        $this->assertDatabaseHas('kanban_tasks', ['id' => $created->id, 'created_by' => $this->member->id]);
        $this->assertSame(1, $this->boardVersion());

        // 抜けたあとはボードを見られない
        $this->actingAs($this->member)->get(route('projects.show', $this->project->id))->assertForbidden();
    }

    public function test_抜けても別のプロジェクトのメンバーのまま(): void
    {
        $other = KanbanProject::factory()->ownedBy($this->owner)->withMember($this->member)->create();
        $otherTask = $this->createTask(TaskStatus::Todo, ['assignee_id' => $this->member->id], $other);

        $this->actingAs($this->member)->delete(route('projects.leave', $this->project->id));

        $this->assertDatabaseHas('kanban_project_user', ['project_id' => $other->id, 'user_id' => $this->member->id]);
        $this->assertDatabaseHas('kanban_tasks', ['id' => $otherTask->id, 'assignee_id' => $this->member->id]);
    }

    public function test_オーナーは抜けられない(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('projects.leave', $this->project->id))
            ->assertSessionHasErrors(['member' => 'オーナーはプロジェクトから抜けられません。先にほかのメンバーへオーナーを引き継いでください。']);

        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'role' => ProjectRole::Owner->value,
        ]);
        $this->assertSame(0, $this->boardVersion());
    }

    public function test_メンバーでない人は抜けられない(): void
    {
        $this->actingAs($this->outsider)
            ->delete(route('projects.leave', $this->project->id))
            ->assertForbidden();

        $this->assertSame(0, $this->boardVersion());
    }

    public function test_存在しないプロジェクトからは抜けられない(): void
    {
        $this->actingAs($this->member)
            ->delete(route('projects.leave', 999999))
            ->assertNotFound();
    }
}
