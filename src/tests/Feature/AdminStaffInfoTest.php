<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AdminStaffInfoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理者が全ユーザーの氏名とメールを確認できる()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list'));

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }
}
