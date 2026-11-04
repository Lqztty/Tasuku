<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->whereNumber(['project', 'user', 'task'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // プロジェクト（作成・編集はモーダルなので create / edit の画面は無い）
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // メンバー
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::patch('projects/{project}/members/{user}/owner', [ProjectMemberController::class, 'transferOwnership'])->name('projects.members.owner');
    Route::delete('projects/{project}/membership', [ProjectMemberController::class, 'leave'])->name('projects.leave');

    // タスク（どのプロジェクトのタスクかは UseCase がタスクから判断し、権限も確かめる）
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
});

require __DIR__.'/settings.php';
