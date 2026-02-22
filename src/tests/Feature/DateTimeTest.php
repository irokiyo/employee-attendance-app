<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateTimeTest extends TestCase
{
    use RefreshDatabase;

    /** 現在の日時情報がUIと同じ形式で出力されている */
    public function test_current_datetime_is_displayed_in_attendance_page(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 29, 9, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.attendance'));

        $response->assertStatus(200);

        // 画面の表示形式に合わせて調整
        $response->assertSee('2026年');
        $response->assertSee('1月29日');
    }
}
