<?php

namespace App\Providers;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\TaskRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Project\EloquentProjectMemberRepository;
use App\Infrastructure\Project\EloquentProjectRepository;
use App\Infrastructure\Shared\LaravelTransactionManager;
use App\Infrastructure\Task\EloquentTaskRepository;
use App\Infrastructure\User\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Domain の Interface と Infrastructure の実装を結び付ける。
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        TransactionManagerInterface::class => LaravelTransactionManager::class,
        ProjectRepositoryInterface::class => EloquentProjectRepository::class,
        ProjectMemberRepositoryInterface::class => EloquentProjectMemberRepository::class,
        TaskRepositoryInterface::class => EloquentTaskRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
    ];
}
