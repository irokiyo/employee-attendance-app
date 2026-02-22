<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    private string $requestListPath = '/stamp_correction_request/list';

    private function adminUser(): User
    {
        return User::factory()->create([
            'status' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /** 承認待ちの修正申請が全て表示されている */
    public function test_pending_shows_all_pending_requests(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();

        $userA = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);
        $userB = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);

        $attA = Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $attB = Attendance::factory()->create([
            'user_id' => $userB->id,
            'date' => '2026-01-30',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
        ]);

        AttendanceRequest::create([
            'user_id' => $userA->id,
            'attendance_id' => $attA->id,
            'break_id' => null,
            'status' => 'pending',
            'payload' => ['start_time' => '09:10:00'],
            'reason' => 'テスト申請A',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        AttendanceRequest::create([
            'user_id' => $userB->id,
            'attendance_id' => $attB->id,
            'break_id' => null,
            'status' => 'pending',
            'payload' => ['start_time' => '10:10:00'],
            'reason' => 'テスト申請B',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $res = $this->actingAs($admin)->get($this->requestListPath.'?status=pending');
        $res->assertStatus(200);

        $res->assertSeeText($userA->name);
        $res->assertSeeText($userB->name);
        $res->assertSeeText('テスト申請A');
        $res->assertSeeText('テスト申請B');
    }

    /** 承認済みの修正申請が全て表示されている */
    public function test_approved_shows_all_approved_requests(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => 'user', 'email_verified_at' => now()]);

        $att = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $att->id,
            'break_id' => null,
            'status' => 'approved',
            'payload' => ['start_time' => '09:15:00'],
            'reason' => '承認済み申請',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $res = $this->actingAs($admin)->get($this->requestListPath.'?status=approved');
        $res->assertStatus(200);

        $res->assertSeeText($user->name);
        $res->assertSeeText('承認済み申請');
    }

    /** 修正申請の詳細内容が正しく表示されている */
    public function test_request_detail_contents_are_displayed_correctly(): void
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

        $req = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'break_id' => null,
            'status' => 'pending',
            'payload' => [
                'start_time' => '09:10:00',
                'end_time' => '18:00:00',
                'breaks' => [
                    ['break_start_time' => '12:00:00', 'break_end_time' => '12:30:00'],
                ],
            ],
            'reason' => '電車遅延',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $list = $this->actingAs($admin)->get('/stamp_correction_request/list?status=pending');
        $list->assertStatus(200);
        $list->assertSee(
            'href="'.route('admin.request.show', ['attendance_correct_request_id' => $req->id]).'"',
            false
        );

        $detail = $this->actingAs($admin)->get(
            route('admin.request.show', ['attendance_correct_request_id' => $req->id])
        );
        $detail->assertStatus(200);
        $detail->assertSeeText('電車遅延');
        $detail->assertSeeText('09:10');
        $detail->assertSeeText('12:00');
        $detail->assertSeeText('12:30');
    }

    /** 修正申請の承認処理が正しく行われる */
    public function test_approve_request_updates_attendance(): void
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

        $req = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'break_id' => null,
            'status' => 'pending',
            'payload' => [
                'start_time' => '09:20:00',
                'end_time' => '18:10:00',
                'breaks' => [
                    ['break_start_time' => '12:00:00', 'break_end_time' => '12:30:00'],
                ],
            ],
            'reason' => '承認テスト',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $approveUrl = route('request.update', ['attendance_correct_request_id' => $req->id]);

        $res = $this->actingAs($admin)
            ->patch(route('request.update', ['attendance_correct_request_id' => $req->id]))
            ->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '09:20:00',
            'end_time' => '18:10:00',
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $req->id,
            'status' => 'approved',
        ]);
    }
}
