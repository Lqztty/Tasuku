<?php

namespace Database\Factories;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Models\KanbanProject;
use App\Models\KanbanProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KanbanProjectMember>
 */
class KanbanProjectMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => KanbanProject::factory(),
            'user_id' => User::factory(),
            'role' => ProjectRole::Member->value,
        ];
    }
}
