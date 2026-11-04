<?php

namespace Database\Factories;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Models\KanbanProject;
use App\Models\KanbanProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KanbanProject>
 */
class KanbanProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'board_version' => 0,
        ];
    }

    /**
     * 指定したユーザーをオーナーとして登録した状態で作る。
     *
     * 「オーナーは必ず1人」をテストデータでも守るため、プロジェクトを作るときは基本これを使う。
     */
    public function ownedBy(User $owner): static
    {
        return $this->afterCreating(function (KanbanProject $project) use ($owner): void {
            KanbanProjectMember::factory()->create([
                'project_id' => $project->id,
                'user_id' => $owner->id,
                'role' => ProjectRole::Owner->value,
            ]);
        });
    }

    /**
     * 指定したユーザーをメンバー（オーナー以外）として追加した状態で作る。
     */
    public function withMember(User $member): static
    {
        return $this->afterCreating(function (KanbanProject $project) use ($member): void {
            KanbanProjectMember::factory()->create([
                'project_id' => $project->id,
                'user_id' => $member->id,
                'role' => ProjectRole::Member->value,
            ]);
        });
    }
}
