<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /** 勤務外ユーザーには「出勤」ボタンが表示される */
    public function test_clock_in_button_is_shown_for_off_duty_user(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('user.attendance'));

        $response->assertStatus(200);
        $response->assertSee('出勤');
    }

    /** 出勤ボタンが正しく機能し、ステータスが勤務中（出勤中）になる */
    public function test_clock_in_creates_attendance_and_status_changes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        $clockInUrl = route('user.attendance');

        $response = $this->actingAs($user)->post($clockInUrl);

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertStatus(200);
        $page->assertSee('出勤中'); // または「勤務中」など、あなたの表示文言に合わせて
    }

    /** 出勤は一日一回のみ：退勤済などの場合、出勤ボタンが表示されない */
    public function test_clock_in_button_is_not_shown_if_already_finished_today(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 18, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.attendance'));

        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /** 出勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_in_time_is_visible_on_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertSee('09:00'); // 一覧側の表示形式に合わせて（09:00:00ならそれに）
    }
}
