<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    // 名前のバリデーション
    /** @test */
    public function testNameValidation()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), $this->validData([
                'name' => '',
            ]));

        $response->assertRedirect(route('register'));

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    // メールアドレスのバリデーション
    public function testEmailValidation()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), $this->validData([
                'email' => '',
            ]));

        $response->assertRedirect(route('register'));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードのバリデーション
    public function testPasswordValidation()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), $this->validData([
                'password' => '',
            ]));

        $response->assertRedirect(route('register'));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // パスワードの8文字以下のバリデーション
    public function testPasswordShortValidation()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), $this->validData([
                'password' => 'pass',
                'password_confirmation' => 'pass',
            ]));

        $response->assertRedirect(route('register'));

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    // パスワードと確認パスワードの不一致のバリデーション
    public function testPasswordMismatchValidation()
    {
        $response = $this->from(route('register'))
            ->post(route('register'), $this->validData([
                'password' => 'pass',
                'password_confirmation' => 'word',
            ]));

        $response->assertRedirect(route('register'));

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /** フォームに内容が入力されていた場合、データが正常に保存される */
    public function testRegisterSuccessSavesUser(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'テスト太郎',
        ]);
    }
}
