<?php

namespace Database\Factories;

use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use App\Models\KanbanTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KanbanTask>
 */
class KanbanTaskFactory extends Factory
{
    /**
     * position は呼び出し側で指定する前提（列ごとの連番はテストごとに意味が違うため）。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => KanbanProject::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Medium->value,
            'assignee_id' => null,
            'due_date' => null,
            'position' => 0,
            'created_by' => null,
        ];
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status->value]);
    }
}
