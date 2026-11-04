<?php

namespace Tests\Feature\Settings;

use App\Models\KanbanProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 「オーナーは必ず1人」を守るため、オーナーのままでは退会できない。
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_オーナーのプロジェクトがあると退会できない(): void
    {
        $owner = User::factory()->create();
        KanbanProject::factory()->ownedBy($owner)->create();

        $response = $this->actingAs($owner)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password']);

        $response->assertSessionHasErrors('password');
        $response->assertRedirect(route('profile.edit'));
        $this->assertAuthenticated();
        $this->assertNotNull($owner->fresh());
    }

    public function test_メンバーとして参加しているだけなら退会でき担当は外れる(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = KanbanProject::factory()->ownedBy($owner)->withMember($member)->create();
        $task = $project->tasks()->create([
            'title' => 'T',
            'status' => 'todo',
            'priority' => 'medium',
            'position' => 0,
            'assignee_id' => $member->id,
            'created_by' => $member->id,
        ]);

        $response = $this->actingAs($member)->delete(route('profile.destroy'), ['password' => 'password']);

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertNull($member->fresh());
        // タスクは残り、担当者と作成者は外部キーの nullOnDelete で null になる
        $this->assertDatabaseHas('kanban_tasks', ['id' => $task->id, 'assignee_id' => null, 'created_by' => null]);
        $this->assertDatabaseMissing('kanban_project_user', ['user_id' => $member->id]);
    }
}
