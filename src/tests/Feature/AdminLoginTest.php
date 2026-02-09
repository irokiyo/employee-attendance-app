<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $adminLoginUrl = '/admin/login';

    /** メールアドレスが未入力の場合、バリデーションメッセージが表示される（管理者） */
    public function test_admin_login_email_required(): void
    {
        $response = $this->from($this->adminLoginUrl)->post($this->adminLoginUrl, [
            'login_type' => 'admin', // あなたのフォームに合わせて
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect($this->adminLoginUrl);
        $response->assertSessionHasErrors(['email']);
        $this->followRedirects($response)->assertSee('メールアドレスを入力してください');
    }

    /** パスワードが未入力の場合、バリデーションメッセージが表示される（管理者） */
    public function test_admin_login_password_required(): void
    {
        $response = $this->from($this->adminLoginUrl)->post($this->adminLoginUrl, [
            'login_type' => 'admin',
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertRedirect($this->adminLoginUrl);
        $response->assertSessionHasErrors(['password']);
        $this->followRedirects($response)->assertSee('パスワードを入力してください');
    }

    /** 登録内容と一致しない場合、バリデーションメッセージが表示される（管理者） */
    public function test_admin_login_invalid_credentials_message(): void
    {
        // 管理者ユーザーの作成（ここはあなたの管理者判定カラムに合わせて調整）
        // 例: is_admin / role / admin_flg など
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            // 'is_admin' => 1,
        ]);

        $response = $this->from($this->adminLoginUrl)->post($this->adminLoginUrl, [
            'login_type' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect($this->adminLoginUrl);
        $response->assertSessionHasErrors();

        $this->followRedirects($response)->assertSee('ログイン情報が登録されていません');
    }
}
