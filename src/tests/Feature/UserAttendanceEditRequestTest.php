<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;

class UserAttendanceEditRequestTest extends TestCase
{
    use RefreshDatabase;

    /** 出勤時間が退勤時間より後ならエラー */
    public function test_start_time_after_end_time_shows_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('user.detail', $attendance->id))
            ->post(route('user.request', ['id' => $attendance->id]), [
                'attendance_id' => $attendance->id,
                'start_time' => '19:00',
                'end_time' => '18:00',
                'reason' => '確認',
            ]);

        $response->assertRedirect(route('user.detail', $attendance->id));
        $response->assertSessionHasErrors(['start_time']);
        $this->followRedirects($response)->assertSee('出勤時間が不適切な値です');
    }

    /** 休憩開始が退勤より後ならエラー */
    public function test_break_start_after_end_time_shows_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('user.detail', $attendance->id))
            ->post(route('user.request', ['id' => $attendance->id]), [
                'attendance_id' => $attendance->id,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'breaks' => [
                    ['break_start_time' => '19:00', 'break_end_time' => '19:10'],
                ],
                'reason' => '確認',
            ]);

        $response->assertRedirect(route('user.detail', $attendance->id));
        $response->assertSessionHasErrors();
        $this->followRedirects($response)->assertSee('休憩時間が不適切な値です');
    }

    /** 休憩終了が退勤より後ならエラー */
    public function test_break_end_after_end_time_shows_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('user.detail', $attendance->id))
            ->post(route('user.request', ['id' => $attendance->id]), [
                'attendance_id' => $attendance->id,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'breaks' => [
                    ['break_start_time' => '17:00', 'break_end_time' => '19:10'],
                ],
                'reason' => '確認',
            ]);

        $response->assertRedirect(route('user.detail', $attendance->id));
        $response->assertSessionHasErrors();
        $this->followRedirects($response)->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /** 備考が未入力ならエラー */
    public function test_reason_required_shows_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('user.detail', $attendance->id))
            ->post(route('user.request', ['id' => $attendance->id]), [
                'attendance_id' => $attendance->id,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'reason' => '',
            ]);

        $response->assertRedirect(route('user.detail', $attendance->id));
        $response->assertSessionHasErrors(['reason']);
        $this->followRedirects($response)->assertSee('備考を記入してください');
    }

    /** 修正申請が実行され、requestsテーブルに保存される */
    public function test_edit_request_is_created(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $this->actingAs($user)->post(route('user.request', ['id' => $attendance->id]), [
            'attendance_id' => $attendance->id,
            'start_time' => '09:10',
            'end_time' => '18:00',
            'breaks' => [
                ['break_start_time' => '12:00', 'break_end_time' => '13:00'],
            ],
            'reason' => '電車遅延のため',
        ])->assertStatus(302);

        $this->assertDatabaseHas('requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);
    }
}
