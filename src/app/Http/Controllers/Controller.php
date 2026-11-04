<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

abstract class Controller
{
    /**
     * ログイン中のユーザー ID。auth ミドルウェアの下でだけ呼ぶ。
     */
    protected function userId(Request $request): int
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user->id;
    }

    /**
     * 次の画面でトーストを出す（hooks/use-flash-toast.ts が sonner で表示する）。
     *
     * @param  'success'|'info'|'warning'|'error'  $type
     */
    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
