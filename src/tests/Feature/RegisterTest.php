<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $registerUrl = '/register';

    /** 名前が未入力の場合、バリデーションメッセージが表示される */
    public function test_register_name_required(): void
    {
        $response = $this->from($this->registerUrl)->post($this->registerUrl, [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect($this->registerUrl);
        $response->assertSessionHasErrors(['name']);

        // 画面に文言が出る実装なら有効（出ない場合はコメントアウト）
        $this->followRedirects($response)->assertSee('お名前を入力してください');
    }

    /** メールアドレスが未入力の場合、バリデーションメッセージが表示される */
    public function test_register_email_required(): void
    {
        $response = $this->from($this->registerUrl)->post($this->registerUrl, [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect($this->registerUrl);
        $response->assertSessionHasErrors(['email']);
        $this->followRedirects($response)->assertSee('メールアドレスを入力してください');
    }

    /** パスワードが8文字未満の場合、バリデーションメッセージが表示される */
    public function test_register_password_min_8(): void
    {
        $response = $this->from($this->registerUrl)->post($this->registerUrl, [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '1234567', // 7文字
            'password_confirmation' => '1234567',
        ]);

        $response->assertRedirect($this->registerUrl);
        $response->assertSessionHasErrors(['password']);
        $this->followRedirects($response)->assertSee('パスワードは8文字以上で入力してください');
    }

    /** パスワードが一致しない場合、バリデーションメッセージが表示される */
    public function test_register_password_confirmation_mismatch(): void
    {
        $response = $this->from($this->registerUrl)->post($this->registerUrl, [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertRedirect($this->registerUrl);
        $response->assertSessionHasErrors(['password']);
        $this->followRedirects($response)->assertSee('パスワードと一致しません');
    }

    /** パスワードが未入力の場合、バリデーションメッセージが表示される */
    public function test_register_password_required(): void
    {
        $response = $this->from($this->registerUrl)->post($this->registerUrl, [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect($this->registerUrl);
        $response->assertSessionHasErrors(['password']);
        $this->followRedirects($response)->assertSee('パスワードを入力してください');
    }

    /** フォームに内容が入力されていた場合、データが正常に保存される */
    public function test_register_success_saves_user(): void
    {
        $response = $this->post($this->registerUrl, [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Fortify標準だと登録後はリダイレクト（メール認証に飛ばす等）
        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'テスト太郎',
        ]);
    }
}
