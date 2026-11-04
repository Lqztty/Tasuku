<?php

namespace App\Http\Controllers;

use App\Application\Task\Commands\CreateTaskCommand;
use App\Application\Task\Commands\MoveTaskCommand;
use App\Application\Task\Commands\UpdateTaskCommand;
use App\Application\Task\CreateTaskUseCase;
use App\Application\Task\DeleteTaskUseCase;
use App\Application\Task\MoveTaskUseCase;
use App\Application\Task\UpdateTaskUseCase;
use App\Http\Requests\TaskRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * タスクの操作。どれも元の画面（ボード）へ戻り、最新の props で画面が置き換わる。
 */
class TaskController extends Controller
{
    public function store(TaskRequest $request, int $project, CreateTaskUseCase $useCase): RedirectResponse
    {
        $useCase->execute(new CreateTaskCommand(
            userId: $this->userId($request),
            projectId: $project,
            title: $request->string('title')->toString(),
            description: $this->nullableString($request, 'description'),
            status: $request->string('status')->toString(),
            priority: $request->string('priority')->toString(),
            assigneeId: $this->nullableInt($request, 'assignee_id'),
            dueDate: $this->nullableString($request, 'due_date'),
        ));

        $this->toast('タスクを追加しました。');

        return back();
    }

    public function update(TaskRequest $request, int $task, UpdateTaskUseCase $useCase): RedirectResponse
    {
        $useCase->execute(new UpdateTaskCommand(
            userId: $this->userId($request),
            taskId: $task,
            title: $request->string('title')->toString(),
            description: $this->nullableString($request, 'description'),
            status: $request->string('status')->toString(),
            priority: $request->string('priority')->toString(),
            assigneeId: $this->nullableInt($request, 'assignee_id'),
            dueDate: $this->nullableString($request, 'due_date'),
        ));

        $this->toast('タスクを更新しました。');

        return back();
    }

    public function destroy(Request $request, int $task, DeleteTaskUseCase $useCase): RedirectResponse
    {
        $useCase->execute($this->userId($request), $task);

        $this->toast('タスクを削除しました。');

        return back();
    }

    /**
     * ドラッグ&ドロップの移動。成功時はトーストを出さない（操作のたびに出るとうるさいため）。
     */
    public function move(TaskRequest $request, int $task, MoveTaskUseCase $useCase): RedirectResponse
    {
        $useCase->execute(new MoveTaskCommand(
            userId: $this->userId($request),
            taskId: $task,
            status: $request->string('status')->toString(),
            position: $request->integer('position'),
            expectedVersion: $request->integer('expected_version'),
        ));

        return back();
    }

    private function nullableString(Request $request, string $key): ?string
    {
        return $request->filled($key) ? $request->string($key)->toString() : null;
    }

    private function nullableInt(Request $request, string $key): ?int
    {
        return $request->filled($key) ? $request->integer($key) : null;
    }
}
