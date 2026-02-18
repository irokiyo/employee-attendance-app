<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /** 出勤ボタンが正しく機能し、ステータスが勤務中（出勤中）になる */
    public function testClockInCreatesAttendanceAndStatusChanges(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('user.attendance'), [
            'action' => 'start',
            ])->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertStatus(200);
        $page->assertSee('出勤中');
    }

    /** 出勤は一日一回のみ */
    public function testClockInButtonIsNotShownIfAlreadyFinishedToday(): void
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
    public function testClockInTTimeIsVisibleOnAttendanceList(): void
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
        $response->assertSee('09:00');
    }
}
