<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRequest;

class AdminAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 承認待ち一覧が表示される()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        AttendanceRequest::factory()->create([
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.request.list', ['status' => 'pending']));

        $response->assertStatus(200);
    }

    /** @test */
    public function 承認処理が正しく行われる()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $request = AttendanceRequest::factory()->create([
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.request.approve', $request->id));

        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request->id,
            'status' => 'approved'
        ]);
    }
}
