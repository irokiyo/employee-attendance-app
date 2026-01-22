<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;


class AttendanceController extends Controller
{
    //勤怠一覧画面(管理者)
    public function adminIndex()
    {
        return view('admin.index');
    }
    //勤務登録画面(一般ユーザー)
    public function userAttendance(Request $request)
    {
        $userId = auth()->id();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();

        $latestBreak = $attendance ? $attendance->breaks()->latest('id')->first(): null;

        $isBreak = $latestBreak && is_null($latestBreak->break_end_time);

        if ($request->isMethod('post')) {
            $action = $request->input('action');

            if ($action === 'start') {
                Attendance::firstOrCreate(
                    ['user_id' => $userId, 'date' => today()],
                    ['start_time' => now()->format('H:i:s')]
                );
            }

            if ($action === 'break_start' && $attendance && !$attendance->end_time) {
                if (!$isBreak) {
                    BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start_time' => now()->format('H:i:s'),
                    ]);
                }
            }

            if ($action === 'break_end' && $attendance) {
                if ($isBreak) {
                    $latestBreak->update([
                    'break_end_time' => now()->format('H:i:s'),
                    ]);
                }
            }

            if ($action === 'end' && $attendance && !$attendance->end_time) {
                if ($isBreak) {
                    $latestBreak->update(['break_end_time' => now()->format('H:i:s')]);
                }
                $attendance->update(['end_time' => now()->format('H:i:s')]);
            }

            return redirect()->route('user.attendance');
        }

        if (!$attendance) {
            $status = 'outside';
        } elseif ($attendance->end_time) {
            $status = 'finished';
        } elseif ($isBreak) {
            $status = 'break';
        } else {
            $status = 'working';
        }

        return view('user.attendance', [
            'attendance' => $attendance,
            'status' => $status,
            'date' => today()->format('Y年n月j日(D)'),
            'time' => now()->format('H:i'),
        ]);
    }
}
