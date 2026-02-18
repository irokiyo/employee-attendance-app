<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面が正しく表示される()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee($attendance->date);
    }

    /** @test */
    public function 出勤時間が退勤時間より後の場合エラー()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.detail.save', $attendance->id), [
                'start_time' => '18:00',
                'end_time'   => '09:00',
            ]);

        $response->assertSessionHasErrors();
    }
}
