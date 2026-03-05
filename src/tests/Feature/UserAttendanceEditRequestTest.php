<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceEditRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $requestListPath = '/stamp_correction_request/list';

    private function attendanceDetailPath(int $attendanceId): string
    {
        return "/attendance/detail/{$attendanceId}";
    }

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
            ->from("/attendance/detail/{$attendance->id}")
            ->post("/attendance/detail/{$attendance->id}", [
                'start_time' => '19:00',
                'end_time' => '18:00',
                'breaks' => [['break_start_time' => '', 'break_end_time' => '']],
                'reason' => '確認',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['start_time']);
        $this->assertSame(
            '出勤時間もしくは退勤時間が不適切な値です',
            session('errors')->first('start_time')
        );
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
            ->post("/attendance/detail/{$attendance->id}", [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'breaks' => [
                    ['break_start_time' => '08:50', 'break_end_time' => '09:10'],
                ],
                'reason' => '確認',
            ]);

        $response->assertStatus(302);
        $follow = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");
        $follow->assertSeeText('休憩時間が不適切な値です');
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

    /** 修正申請が実行され、requestsテーブルに保存 */
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

    /** 「承認待ち」にログインユーザーが行った申請が全て表示されている */
    public function test_pending_tab_shows_all_requests_of_logged_in_user(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'user',
        ]);
        $other = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'user',
        ]);

        $att1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);
        $att2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-30',
            'start_time' => '09:00:00',
        ]);
        $otherAtt = Attendance::factory()->create([
            'user_id' => $other->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);
        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $att1->id,
            'status' => 'pending',
            'payload' => ['start_time' => '09:10:00'],
            'reason' => 'テスト申請',
        ]);
        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $att2->id,
            'status' => 'pending',
            'payload' => ['start_time' => '09:20:00'],
            'reason' => 'テスト申請',
        ]);
        AttendanceRequest::create([
            'user_id' => $other->id,
            'attendance_id' => $otherAtt->id,
            'status' => 'pending',
            'payload' => ['start_time' => '09:30:00'],
            'reason' => 'テスト申請',
        ]);

        $response = $this->actingAs($user)->get($this->requestListPath.'?status=pending');
        $response->assertStatus(200);

        $response->assertSee("/attendance/detail/{$att1->id}");
        $response->assertSee("/attendance/detail/{$att2->id}");

        $response->assertDontSeeText($other->name);

        $response->assertDontSee("/attendance/detail/{$otherAtt->id}");
    }

    /** 「承認済み」に管理者が承認した修正申請が全て表示されている */
    public function test_approved_tab_shows_all_requests_approved(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $att1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $att1->id,
            'status' => 'approved',
            'payload' => ['start_time' => '09:15:00'],
            'reason' => 'テスト',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=approved');
        $response->assertStatus(200);

        $response->assertSee((string) $att1->id);
    }

    /** 各申請の「詳細」を押下すると勤怠詳細画面に遷移する */
    public function test_detail_link_navigates_to_attendance_detail_page(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'payload' => ['start_time' => '09:10:00'],
            'reason' => 'テスト申請',
        ]);

        $list = $this->actingAs($user)->get($this->requestListPath.'?status=pending');
        $list->assertStatus(200);
        $list->assertSee($this->attendanceDetailPath($attendance->id));

        $detail = $this->actingAs($user)->get($this->attendanceDetailPath($attendance->id));
        $detail->assertStatus(200);
    }
}
