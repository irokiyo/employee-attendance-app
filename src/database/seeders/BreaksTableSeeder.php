<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\BreakTime;

class BreaksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {

            $reinaId = DB::table('users')
                ->where('email', 'reina.n@coachtech.com')
                ->value('id');

            $otherUserIds = DB::table('users')
                ->where('email', '!=', 'reina.n@coachtech.com')
                ->pluck('id')
                ->toArray();

            $targetUserIds = array_values(array_filter(array_merge([$reinaId], $otherUserIds)));

            if (empty($targetUserIds)) {
                return;
            }

            $attendanceIds = DB::table('attendances')
                ->whereIn('user_id', $targetUserIds)
                ->whereBetween('date', ['2023-06-01', '2023-06-30'])
                ->pluck('id')
                ->toArray();

            if (empty($attendanceIds)) {
                return;
            }

            DB::table('breaks')
                ->whereIn('attendance_id', $attendanceIds)
                ->delete();


            foreach ($attendanceIds as $attendanceId) {
                BreakTime::create([
                    'attendance_id' => $attendanceId,
                    'break_start_time' => '11:00:00',
                    'break_end_time'   => '12:00:00',
                ]);
            }
        });
    }
}
