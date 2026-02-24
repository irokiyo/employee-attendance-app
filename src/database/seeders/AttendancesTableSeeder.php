<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {
            $renaId = DB::table('users')
                ->where('email', 'reina.n@coachtech.com')
                ->value('id');

            if ($renaId) {
                $period = CarbonPeriod::create('2025-12-01', '2025-12-31');
                foreach ($period as $day) {
                    if ($day->isWeekend()) {
                        continue;
                    }

                    DB::table('attendances')->insert([
                        'user_id' => $renaId,
                        'date' => $day->toDateString(),
                        'start_time' => '09:00:00',
                        'end_time' => '18:00:00',
                    ]);
                }
            }

            $otherUserIds = DB::table('users')
                ->where('email', '!=', 'reina.n@coachtech.com')
                ->pluck('id');

            foreach ($otherUserIds as $userId) {
                for ($i = 0; $i < 15; $i++) {

                    $day = Carbon::create(2023, 6, 1)->addDays(random_int(0, 29));

                    $startHour = random_int(7, 11);
                    $startMin = [0, 30][random_int(0, 1)];

                    $workHours = random_int(3, 8);
                    $endHour = $startHour + $workHours;
                    $endMin = $startMin;

                    DB::table('attendances')->insert([
                        'user_id' => $userId,
                        'date' => $day->toDateString(),
                        'start_time' => sprintf('%02d:%02d:00', $startHour, $startMin),
                        'end_time' => sprintf('%02d:%02d:00', $endHour, $endMin),
                    ]);
                }
            }
        });
    }
}
