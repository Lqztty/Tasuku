<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 認証まわりのエラーが lang/ja の日本語で返ることを確かめる
 */
class JapaneseMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインに失敗すると日本語のエラーが返る(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
    }

    public function test_必須項目が空だと項目名つきの日本語のエラーが返る(): void
    {
        $this->post(route('login.store'), [
            'email' => '',
            'password' => '',
        ])->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
            'password' => 'パスワードを入力してください。',
        ]);
    }
}
