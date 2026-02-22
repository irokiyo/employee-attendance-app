<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /** 退勤ボタンが正しく機能する */
    public function test_clock_out_button_is_shown_and_clock_out_changes_status(): void
    {
        Carbon::setTestNow('2026-01-29 17:50:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get(route('user.attendance'));
        $response->assertStatus(200);
        $response->assertSee('退勤');

        Carbon::setTestNow('2026-01-29 18:00:00');

        $this->actingAs($user)->post(route('user.attendance'), [
            'action' => 'end',
        ])->assertStatus(302);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertStatus(200);
        $page->assertSee('退勤済');
    }

    /** 退勤時刻が勤怠一覧画面に正しく表示される */
    public function test_clock_out_time_is_visible_on_list(): void
    {
        Carbon::setTestNow('2026-01-29 18:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertSee('18:00');
    }
}
