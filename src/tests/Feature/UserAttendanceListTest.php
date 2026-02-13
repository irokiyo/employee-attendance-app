<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** 自分の勤怠情報のみが全て表示される */
    public function test_only_own_attendance_is_displayed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
        ]);

        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-01-01',
        ]);

        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertSee('2026-01-01');
        $response->assertDontSee((string) $otherUser->id);
    }

    /** 勤怠一覧画面に現在の月が表示される */
    public function test_current_month_is_displayed(): void
    {
        Carbon::setTestNow('2026-01-15');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertSee('2026年1月');
    }

    /** 前月ボタンで前月の勤怠が表示される */
    public function test_previous_month_attendance_is_displayed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-12-10',
        ]);

        $response = $this->actingAs($user)->get(route('user.index', [
            'month' => '2025-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2025-12-10');
    }

    /** 詳細ボタンを押すと勤怠詳細画面へ遷移する */
    public function test_detail_button_navigates_to_detail_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
        ]);

        $response = $this->actingAs($user)->get(route('user.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
