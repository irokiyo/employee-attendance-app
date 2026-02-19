<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** 勤怠詳細画面に表示されるデータが選択したものになっている */
    public function testAttendanceDetailsScreenIsDisplayedCorrectly()
    {
        $admin = User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);
        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($attendance->date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->date)->format('n月j日'));
    }
    /** 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される*/
    public function testStartTimeAfterEndTimeShowsError(): void
    {
        $admin = User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.detail', $attendance->id))
            ->post(route('admin.detail.save', $attendance->id), [
                'start_time' => '19:00',
                'end_time'   => '18:00',
                'breaks' => [
                    ['break_start_time' => '', 'break_end_time' => ''],
                ],
                'reason' => '確認',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['start_time']);

        $this->assertSame(
            '出勤時間もしくは退勤時間が不適切な値です',
            session('errors')->first('start_time')
        );
    }

    /** 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function testBreakStartOutsideWorkTimeShowsError(): void
    {
        $admin = User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.detail', $attendance->id))
            ->post(route('admin.detail.save', $attendance->id), [
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'breaks' => [
                    ['break_start_time' => '08:50', 'break_end_time' => '09:10'],
                ],
                'reason' => '確認',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.break_start_time']);

        $this->assertSame(
            '休憩時間が不適切な値です',
            session('errors')->first('breaks.0.break_start_time')
        );
    }

    /** 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function testBreakEndAfterEndTimeShowsError(): void
    {
        $admin = User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.detail', $attendance->id))
            ->post(route('admin.detail.save', $attendance->id), [
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'breaks' => [
                    ['break_start_time' => '17:00', 'break_end_time' => '19:10'],
                ],
                'reason' => '確認',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['end_time']);

        $this->assertSame(
            '休憩時間もしくは退勤時間が不適切な値です',
            session('errors')->first('end_time')
        );
    }

    /** 備考欄が未入力の場合のエラーメッセージが表示される*/
    public function testReasonRequiredShowsError(): void
    {
        $admin = User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.detail', $attendance->id))
            ->post(route('admin.detail.save', $attendance->id), [
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'breaks' => [
                    ['break_start_time' => '', 'break_end_time' => ''],
                ],
                'reason' => '',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['reason']);

        $this->assertSame(
            '備考を記入してください',
            session('errors')->first('reason')
        );
    }

}
