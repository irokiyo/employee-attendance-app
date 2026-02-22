<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffInfoTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /** 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる */
    public function test_admin_can_aee_all_users_name_and_email(): void
    {
        $admin = $this->adminUser();
        $users = User::factory()->count(3)->create(['status' => 'user', 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.staff.index'));
        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /** ユーザーの勤怠情報が正しく表示される */
    public function test_attendance_list_displays_correctly_for_selected_user(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', ['id' => $user->id]));

        $response->assertStatus(200);
        $response->assertSee('01/29');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** 「前月」を押下した時に表示月の前月の情報が表示される */
    public function test_previous_month_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');

        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-12-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', [
            'id' => $user->id,
            'month' => '2025-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('12/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** 「翌月」を押下した時に表示月の前月の情報が表示される */
    public function test_next_month_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');

        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-10',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', [
            'id' => $user->id,
            'month' => '2026-02',
        ]));

        $response->assertStatus(200);
        $response->assertSee('02/10');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /** 「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
    public function test_detail_link_navigates_to_detail_page(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $list = $this->actingAs($admin)->get(route('admin.index', [
            'user_id' => $user->id,
            'month' => '2026-01',
        ]));
        $list->assertStatus(200);
        $list->assertSee('/admin/attendance/'.$attendance->id);

        $detail = $this->actingAs($admin)->get(route('admin.detail', $attendance->id));
        $detail->assertStatus(200);
        $detail->assertSee(Carbon::parse($attendance->date)->format('Y年'));
        $detail->assertSee(Carbon::parse($attendance->date)->format('n月j日'));
    }
}
