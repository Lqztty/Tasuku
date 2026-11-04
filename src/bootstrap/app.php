<?php

use App\Domain\Shared\Exceptions\ConflictException;
use App\Domain\Shared\Exceptions\ForbiddenException;
use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ドメイン例外 → HTTP の応答。UseCase は HTTP を知らずに例外を投げるだけにする
        $exceptions->map(
            ForbiddenException::class,
            fn (ForbiddenException $e) => new AccessDeniedHttpException($e->getMessage(), $e),
        );
        $exceptions->map(
            NotFoundException::class,
            fn (NotFoundException $e) => new NotFoundHttpException($e->getMessage(), $e),
        );
        $exceptions->map(
            InvalidInputException::class,
            fn (InvalidInputException $e) => ValidationException::withMessages([$e->field => $e->getMessage()]),
        );

        // 競合は異常ではないのでログに残さない。元の画面へ戻すと最新の状態が表示される
        $exceptions->dontReport(ConflictException::class);
        $exceptions->render(function (ConflictException $e) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => $e->getMessage()]);

            return back();
        });
    })->create();
