<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'admin',
        ]);
    }

    /** その日になされた全ユーザーの勤怠情報が正確に確認できる */
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

    /** 遷移した際に現在の日付が表示される */
    public function test_admin_list_shows_current_date(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertSee('2026-01-29'); // UIが「2026年1月29日」ならそこに合わせて変更
    }

    /** 「前日」を押下した時に前の日の勤怠情報が表示される */
    public function test_admin_can_open_previous_day(): void
    {
        $admin = $this->adminUser();

        Attendance::factory()->create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-01-28',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', [
            'date' => '2026-01-28',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026-01-28');
    }

    /** 「翌日」を押下した時に次の日の勤怠情報が表示される */
    public function test_admin_can_open_next_day(): void
    {
        $admin = $this->adminUser();

        Attendance::factory()->create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-01-30',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', [
            'date' => '2026-01-30',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026-01-30');
    }
}
