<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    /** 出勤中のユーザーに「休憩入」ボタンが表示される */
    public function test_break_in_button_is_shown(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('user.attendance'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }

    /** 休憩入を行うとステータスが「休憩中」になる */
    public function test_break_in_changes_status(): void
    {
        Carbon::setTestNow('2026-01-29 12:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        $this->actingAs($user)->post(route('user.attendance'), [
            'action' => 'break_in',
        ])->assertStatus(302);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertSee('休憩中');
    }

    /** 休憩は一日に何回でもできる */
    public function test_break_can_be_done_multiple_times(): void
    {
        Carbon::setTestNow('2026-01-29 12:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        // 休憩 → 戻り → 再休憩
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_in']);
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_out']);
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_in']);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertSee('休憩中');
    }
}
