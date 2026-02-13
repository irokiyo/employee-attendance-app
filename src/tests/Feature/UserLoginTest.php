<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/login';

    /** メールアドレスが未入力の場合、バリデーションメッセージが表示される */
    public function test_login_email_required(): void
    {
        $response = $this->from($this->loginUrl)->post($this->loginUrl, [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect($this->loginUrl);
        $response->assertSessionHasErrors(['email']);
        $this->followRedirects($response)->assertSee('メールアドレスを入力してください');
    }

    /** パスワードが未入力の場合、バリデーションメッセージが表示される */
    public function test_login_password_required(): void
    {
        $response = $this->from($this->loginUrl)->post($this->loginUrl, [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect($this->loginUrl);
        $response->assertSessionHasErrors(['password']);
        $this->followRedirects($response)->assertSee('パスワードを入力してください');
    }

    /** 登録内容と一致しない場合、バリデーションメッセージが表示される */
    public function test_login_invalid_credentials_message(): void
    {
        // 登録ユーザーを作る
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 間違ったパスワードでログイン
        $response = $this->from($this->loginUrl)->post($this->loginUrl, [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect($this->loginUrl);

        // Fortify/標準Authだと errors は email に乗ることが多い
        $response->assertSessionHasErrors();

        $this->followRedirects($response)->assertSee('ログイン情報が登録されていません');
    }
}
