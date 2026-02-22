<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'break_start_time' => '12:00:00',
            'break_end_time' => '12:30:00',
        ];
    }
}
