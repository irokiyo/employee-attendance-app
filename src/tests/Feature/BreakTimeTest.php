<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    /** 休憩ボタンが正しく機能する */
    public function testBreakInButtonIsShownAndBreakInChangesStatus(): void
    {
        Carbon::setTestNow('2026-01-29 10:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time'   => null,
        ]);

        $response = $this->actingAs($user)->get(route('user.attendance'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        Carbon::setTestNow('2026-01-29 12:00:00');

        $this->actingAs($user)->post(route('user.attendance'), [
            'action' => 'break_start',
        ])->assertStatus(302);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertStatus(200);
        $page->assertSee('休憩中');
    }

    /** 休憩は一日に何回でもできる */
    public function testBreakCanBeDoneMultipleTimes(): void
    {
        Carbon::setTestNow('2026-01-29 12:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-29',
            'start_time' => '09:00:00',
        ]);

        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_start']);
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_end']);
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_start']);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertSee('休憩中');
    }

    /** 休憩戻ボタンが正しく機能する */
    public function testBreakOutWorksAndStatusBecomesWorking(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Carbon::setTestNow('2026-01-29 09:00:00');
        Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time'   => null,
        ]);

        Carbon::setTestNow('2026-01-29 12:00:00');
        $this->actingAs($user)->post(route('user.attendance'), [
            'action' => 'break_start',
        ])->assertStatus(302);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertStatus(200);
        $page->assertSee('休憩戻');

        Carbon::setTestNow('2026-01-29 12:30:00');
        $this->actingAs($user)->post(route('user.attendance'), [
            'action' => 'break_end',
        ])->assertStatus(302);

        $page2 = $this->actingAs($user)->get(route('user.attendance'));
        $page2->assertStatus(200);
        $page2->assertSee('出勤中');
    }

    /** 休憩戻は1日に何回でもできる */
    public function testBreakOutCanBeDoneAnyTimes(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Carbon::setTestNow('2026-01-29 09:00:00');
        Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time'   => null,
        ]);

        Carbon::setTestNow('2026-01-29 12:00:00');
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_start'])
            ->assertStatus(302);

        Carbon::setTestNow('2026-01-29 12:30:00');
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_end'])
            ->assertStatus(302);

        Carbon::setTestNow('2026-01-29 13:00:00');
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_start'])
            ->assertStatus(302);

        $page = $this->actingAs($user)->get(route('user.attendance'));
        $page->assertStatus(200);
        $page->assertSee('休憩戻');
    }

    /** 休憩時刻が勤怠一覧画面で確認できる */
    public function testBreakTimesAreVisibleOnAttendanceList(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Carbon::setTestNow('2026-01-29 09:00:00');
        Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => '2026-01-29',
            'start_time' => '09:00:00',
            'end_time'   => null,
        ]);

        Carbon::setTestNow('2026-01-29 12:00:00');
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_start'])
            ->assertStatus(302);

        Carbon::setTestNow('2026-01-29 12:30:00');
        $this->actingAs($user)->post(route('user.attendance'), ['action' => 'break_end'])
            ->assertStatus(302);

        $list = $this->actingAs($user)->get(route('user.index'));
        $list->assertStatus(200);

        $list->assertSee('0:30');
}


}
