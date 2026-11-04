<?php

namespace App\Http\Controllers;

use App\Application\ProjectMember\AddProjectMemberUseCase;
use App\Application\ProjectMember\RemoveProjectMemberUseCase;
use App\Application\ProjectMember\TransferProjectOwnershipUseCase;
use App\Http\Requests\ProjectMemberRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function store(ProjectMemberRequest $request, int $project, AddProjectMemberUseCase $useCase): RedirectResponse
    {
        $useCase->execute($this->userId($request), $project, $request->string('email')->toString());

        $this->toast('メンバーを追加しました。');

        return back();
    }

    public function destroy(Request $request, int $project, int $user, RemoveProjectMemberUseCase $useCase): RedirectResponse
    {
        $useCase->execute($this->userId($request), $project, $user);

        $this->toast('メンバーを外しました。');

        return back();
    }

    public function transferOwnership(Request $request, int $project, int $user, TransferProjectOwnershipUseCase $useCase): RedirectResponse
    {
        $useCase->execute($this->userId($request), $project, $user);

        $this->toast('オーナーを引き継ぎました。');

        return back();
    }
}
