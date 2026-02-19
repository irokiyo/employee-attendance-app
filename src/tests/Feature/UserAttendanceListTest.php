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

    /** 自分が行った勤怠情報が全て表示されている */
    public function testOnlyOwnAttendanceIsDisplayed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'start_time' => '09:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-01-10',
            'start_time' => '10:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.index', ['month' => '2026-01']));
        $response->assertStatus(200);

        $response->assertSee('01/01');
        $response->assertSee('09:00');

        $response->assertDontSee('10:00');
    }

    /** 勤怠一覧画面に遷移した際に現在の月が表示される */
    public function testCurrentMonthIsDisplayed(): void
    {
        Carbon::setTestNow('2026-01-15');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertSee('2026/01');
    }

    /** [前月]を押した時に表示月の前月の情報が表示される */
    public function testPreviousMonthAttendanceIsDisplayed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-12-10',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.index', [
            'month' => '2025-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('12/10');
    }

    /** [翌月]を押した時に表示月の翌月の情報が表示される */
    public function testNextMonthAttendanceIsDisplayed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-10',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.index', [
            'month' => '2026-02',
        ]));

        $response->assertStatus(200);
        $response->assertSee('02/10');
    }

    /** [詳細]ボタンを押すとその日の勤怠詳細画面へ遷移する */
    public function testDetailButtonNavigatesToDetailPage(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
