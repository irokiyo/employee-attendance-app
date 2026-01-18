<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Request;


class RequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {

            $applyAt = Carbon::parse('2023-08-01 09:00:00');
            $targetDate = '2023-06-01';

            $targetEmails = [
                'reina.n@coachtech.com',
                'taro.y@coachtech.com',
            ];

            foreach ($targetEmails as $email) {
                $userId = DB::table('users')->where('email', $email)->value('id');

                if (!$userId) {
                    continue;
                }

                $attendanceId = DB::table('attendances')
                    ->where('user_id', $userId)
                    ->where('date', $targetDate)
                    ->value('id');

                if (!$attendanceId) {
                    continue;
                }

                $breakId = DB::table('breaks')
                    ->where('attendance_id', $attendanceId)
                    ->orderBy('id')
                    ->value('id');

                if (!$breakId) {
                    continue;
                }

                // 4) payload(JSON) ＝変更後の内容
                $payload = [
                    'break' => [
                        'id' => $breakId,
                        'break_start_time' => '12:00:00',
                        'break_end_time'   => '13:00:00',
                    ],
                ];

                $exists = Request::where('user_id', $userId)
                    ->where('attendance_id', $attendanceId)
                    ->where('status', 'pending')
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('requests')->insert([
                    'user_id' => $userId,
                    'attendance_id' => $attendanceId,
                    'break_id' => $breakId,
                    'status' => 'pending',
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'reason' => '電車遅延のため',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'created_at' => $applyAt,
                    'updated_at' => $applyAt,
                ]);
            }
        });
    }
}
