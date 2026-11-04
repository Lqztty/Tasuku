<?php

namespace App\Http\Controllers;

use App\Application\Task\GetMyTaskListUseCase;
use App\Http\Resources\AssignedTaskResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * マイタスク（ログイン後の最初の画面）。
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, GetMyTaskListUseCase $useCase): Response
    {
        return Inertia::render('dashboard', [
            'tasks' => AssignedTaskResource::collection($useCase->execute($this->userId($request)))->resolve(),
        ]);
    }
}
