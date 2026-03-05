<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** 詳細画面の名前がログインユーザー名になっている */
    public function testDetailShowsLoggedInUserName(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
        ]);

        $response = $this->actingAs($user)->get(route('user.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
    }

    /** 詳細画面の日付が選択した日付になっている */
    public function testDetailShowSelectedDate(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
        ]);

        $response = $this->actingAs($user)->get(route('user.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee('2026年');
        $response->assertSee('1月29日');
    }

    /** 出勤・退勤の時間が打刻と一致している */
    public function testDetailShowsStartAndEndTime(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** 休憩の時間が打刻と一致している */
    public function tesDetailShowsBreakTime(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
