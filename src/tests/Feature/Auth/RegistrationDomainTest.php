<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 新規登録とメールアドレスの変更を、許可したドメインだけに制限する。
 */
class RegistrationDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
        config(['tasuku.allowed_email_domains' => ['tasuku.example.jp']]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedEmails(): array
    {
        return [
            '小文字' => ['taro@tasuku.example.jp'],
            '大文字を含む' => ['Taro@TASUKU.Example.JP'],
        ];
    }

    #[DataProvider('allowedEmails')]
    public function test_許可したドメインなら登録できる(string $email): void
    {
        $response = $this->post(route('register.store'), $this->form($email));

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rejectedEmails(): array
    {
        return [
            '別のドメイン' => ['taro@gmail.com'],
            'サブドメイン' => ['taro@sub.tasuku.example.jp'],
            '末尾だけ一致' => ['taro@eviltasuku.example.jp'],
        ];
    }

    #[DataProvider('rejectedEmails')]
    public function test_許可していないドメインは登録できない(string $email): void
    {
        $response = $this->post(route('register.store'), $this->form($email));

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_許可リストが空なら誰も登録できない(): void
    {
        config(['tasuku.allowed_email_domains' => []]);

        $response = $this->post(route('register.store'), $this->form('taro@tasuku.example.jp'));

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_登録後に許可していないドメインへメールアドレスを変えられない(): void
    {
        $user = User::factory()->create(['email' => 'taro@tasuku.example.jp']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taro@gmail.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame('taro@tasuku.example.jp', $user->refresh()->email);
    }

    /**
     * @return array<string, string>
     */
    private function form(string $email): array
    {
        return [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
