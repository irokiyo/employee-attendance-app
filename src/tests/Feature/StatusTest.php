<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    private function openAttendancePageAs(User $user)
    {
        return $this->actingAs($user)->get(route('user.attendance'));
    }

    /** 勤務外の場合、勤怠ステータスが「勤務外」と表示される */
    public function test_status_off_duty_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        // 今日のAttendanceを作らない＝勤務外想定
        $response = $this->openAttendancePageAs($user);

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /** 出勤中の場合、勤怠ステータスが「出勤中」と表示される */
    public function test_status_working_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->openAttendancePageAs($user);

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /** 休憩中の場合、勤怠ステータスが「休憩中」と表示される */
    public function test_status_on_break_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 12, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        // 休憩中判定があなたの実装依存です。
        // 例: breaksテーブルに break_start_time はあるが break_end_time が null の休憩が存在する
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        // Breakモデルがある前提（あなたのアプリに合わせて）
        \App\Models\BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => null,
        ]);

        $response = $this->openAttendancePageAs($user);

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /** 退勤済の場合、勤怠ステータスが「退勤済」と表示される */
    public function test_status_finished_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 18, 0, 0));

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->openAttendancePageAs($user);

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
