<?php

namespace Tests\Feature\Kanban;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Models\KanbanProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use BuildsKanbanBoard;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBoard();
    }

    public function test_ログインしていなければログイン画面へ戻される(): void
    {
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }

    public function test_一覧には自分が参加しているプロジェクトだけが出る(): void
    {
        KanbanProject::factory()->ownedBy(User::factory()->create())->create(['name' => '他人のプロジェクト']);
        $this->createTask();

        $this->actingAs($this->member)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/index')
                ->has('projects', 1)
                ->where('projects.0.id', $this->project->id)
                ->where('projects.0.role', ProjectRole::Member->value)
                ->where('projects.0.task_count', 1)
                ->where('projects.0.member_count', 2));

        $this->actingAs($this->outsider)
            ->get(route('projects.index'))
            ->assertInertia(fn (Assert $page) => $page->component('projects/index')->has('projects', 0));
    }

    public function test_作成した人がオーナーになりボードへ移動する(): void
    {
        $response = $this->actingAs($this->outsider)->post(route('projects.store'), [
            'name' => '新しいプロジェクト',
            'description' => '説明',
        ]);

        $project = KanbanProject::query()->where('name', '新しいプロジェクト')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project->id));
        $response->assertInertiaFlash('toast.type', 'success');
        $this->assertDatabaseHas('kanban_project_user', [
            'project_id' => $project->id,
            'user_id' => $this->outsider->id,
            'role' => ProjectRole::Owner->value,
        ]);
        $this->assertDatabaseCount('kanban_project_user', 3); // 既存の2行 + 新しいオーナー1行
    }

    public function test_プロジェクト名は必須で100文字まで(): void
    {
        $this->actingAs($this->owner)
            ->post(route('projects.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'プロジェクト名を入力してください。']);

        $this->actingAs($this->owner)
            ->post(route('projects.store'), ['name' => str_repeat('あ', 101)])
            ->assertSessionHasErrors('name');
    }

    public function test_オーナーのボードには管理権限が付く(): void
    {
        $this->actingAs($this->owner)
            ->get(route('projects.show', $this->project->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/show')
                ->where('project.id', $this->project->id)
                ->where('project.board_version', 0)
                ->where('role', 'owner')
                ->where('permissions.manage_project', true)
                ->where('permissions.delete_any_task', true)
                ->where('permissions.leave_project', false)
                ->has('members', 2)
                ->where('members.0.user_id', $this->owner->id) // オーナーが先頭
                ->has('tasks', 0));
    }

    public function test_メンバーのボードには管理権限が付かない(): void
    {
        $this->actingAs($this->member)
            ->get(route('projects.show', $this->project->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/show')
                ->where('role', 'member')
                ->where('permissions.manage_project', false)
                ->where('permissions.delete_any_task', false)
                ->where('permissions.leave_project', true));
    }

    public function test_メンバー以外はボードを見られない(): void
    {
        $this->actingAs($this->outsider)->get(route('projects.show', $this->project->id))->assertForbidden();
    }

    public function test_存在しないプロジェクトと数字以外の_id_は404(): void
    {
        $this->actingAs($this->owner)->get(route('projects.show', 999999))->assertNotFound();
        $this->actingAs($this->owner)->get('/projects/abc')->assertNotFound();
    }

    public function test_オーナーはプロジェクトを編集できる(): void
    {
        $this->actingAs($this->owner)
            ->put(route('projects.update', $this->project->id), ['name' => '改名', 'description' => null])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kanban_projects', ['id' => $this->project->id, 'name' => '改名', 'description' => null]);
    }

    public function test_オーナー以外はプロジェクトを編集できない(): void
    {
        foreach ([$this->member, $this->outsider] as $user) {
            $this->actingAs($user)
                ->put(route('projects.update', $this->project->id), ['name' => '改名'])
                ->assertForbidden();
        }

        $this->assertDatabaseHas('kanban_projects', ['id' => $this->project->id, 'name' => $this->project->name]);
    }

    public function test_オーナーが削除するとメンバーとタスクも消える(): void
    {
        $this->createTask();

        $this->actingAs($this->owner)
            ->delete(route('projects.destroy', $this->project->id))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('kanban_projects', ['id' => $this->project->id]);
        $this->assertDatabaseCount('kanban_project_user', 0);
        $this->assertDatabaseCount('kanban_tasks', 0);
    }

    public function test_オーナー以外はプロジェクトを削除できない(): void
    {
        foreach ([$this->member, $this->outsider] as $user) {
            $this->actingAs($user)->delete(route('projects.destroy', $this->project->id))->assertForbidden();
        }

        $this->assertDatabaseHas('kanban_projects', ['id' => $this->project->id]);
    }
}
