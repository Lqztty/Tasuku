<?php

namespace App\Http\Controllers;

use App\Application\Project\Commands\CreateProjectCommand;
use App\Application\Project\Commands\UpdateProjectCommand;
use App\Application\Project\CreateProjectUseCase;
use App\Application\Project\DeleteProjectUseCase;
use App\Application\Project\GetProjectBoardUseCase;
use App\Application\Project\GetProjectListUseCase;
use App\Application\Project\UpdateProjectUseCase;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\MemberResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Http\Resources\TaskResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, GetProjectListUseCase $useCase): Response
    {
        return Inertia::render('projects/index', [
            'projects' => ProjectSummaryResource::collection($useCase->execute($this->userId($request)))->resolve(),
        ]);
    }

    public function store(ProjectRequest $request, CreateProjectUseCase $useCase): RedirectResponse
    {
        $project = $useCase->execute(new CreateProjectCommand(
            userId: $this->userId($request),
            name: $request->string('name')->toString(),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        ));

        $this->toast('プロジェクトを作成しました。');

        return to_route('projects.show', ['project' => $project->id]);
    }

    public function show(Request $request, int $project, GetProjectBoardUseCase $useCase): Response
    {
        $board = $useCase->execute($this->userId($request), $project);

        return Inertia::render('projects/show', [
            'project' => (new ProjectResource($board->project))->resolve(),
            'role' => $board->role->value,
            'members' => MemberResource::collection($board->members)->resolve(),
            'tasks' => TaskResource::collection($board->tasks)->resolve(),
            'permissions' => [
                'manage_project' => $board->canManageProject,
                'delete_any_task' => $board->canDeleteAnyTask,
                'leave_project' => $board->canLeaveProject,
            ],
        ]);
    }

    public function update(ProjectRequest $request, int $project, UpdateProjectUseCase $useCase): RedirectResponse
    {
        $useCase->execute(new UpdateProjectCommand(
            userId: $this->userId($request),
            projectId: $project,
            name: $request->string('name')->toString(),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        ));

        $this->toast('プロジェクトを更新しました。');

        return back();
    }

    public function destroy(Request $request, int $project, DeleteProjectUseCase $useCase): RedirectResponse
    {
        $useCase->execute($this->userId($request), $project);

        $this->toast('プロジェクトを削除しました。');

        return to_route('projects.index');
    }
}
