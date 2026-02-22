<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $adminLoginUrl = '/admin/login';

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'password',
        ], $overrides);
    }

    // emailのバリデーション
    public function test_login_email_validation()
    {
        $response = $this->from(route('login'))
            ->post(route('login'), $this->validData([
                'email' => '',
            ]));

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // passwordのバリデーション
    public function test_login_password_validation()
    {
        $response = $this->from(route('login'))
            ->post(route('login'), $this->validData([
                'password' => '',
            ]));

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 入力情報が違うときのバリデーション
    public function test_login_mismatch_validation()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from(route('login'))
            ->post(route('login'), $this->validData([
                'email' => '123@example.com',
                'password' => 'pass',
            ]));

        $response->assertRedirect(route('login'));

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
