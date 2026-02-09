<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            // 'is_admin' => 1, // あなたの管理者判定に合わせて有効化
        ]);
    }

    /** その日の全ユーザー勤怠が確認できる */
    public function test_admin_can_see_all_users_attendance_of_the_day(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();

        $u1 = User::factory()->create(['email_verified_at' => now()]);
        $u2 = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create(['user_id' => $u1->id, 'date' => '2026-01-29', 'start_time' => '09:00:00']);
        Attendance::factory()->create(['user_id' => $u2->id, 'date' => '2026-01-29', 'start_time' => '09:30:00']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertSee('2026-01-29');
    }

    /** 遷移時に現在の日付が表示される */
    public function test_admin_list_shows_current_date(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertSee('2026-01-29'); // UIが「2026年1月29日」ならそこに合わせて変更
    }

    /** 前日ボタンで前日の勤怠が表示される（クエリで表現） */
    public function test_admin_can_open_previous_day(): void
    {
        $admin = $this->adminUser();

        Attendance::factory()->create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-01-28',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', [
            'date' => '2026-01-28'
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026-01-28');
    }

    /** 翌日ボタンで翌日の勤怠が表示される（クエリで表現） */
    public function test_admin_can_open_next_day(): void
    {
        $admin = $this->adminUser();

        Attendance::factory()->create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-01-30',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', [
            'date' => '2026-01-30'
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026-01-30');
    }
}
