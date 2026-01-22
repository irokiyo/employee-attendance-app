<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    //勤怠一覧画面(管理者)
    public function adminIndex()
    {
        return view('admin.index');
    }
    //勤務登録画面
    public function userAttendance(Request $request)
    {
        $userId = auth()->id();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();

        if ($request->isMethod('post')) {
            $action = $request->input('action'); // start / end / break_start / break_end

            if ($action === 'start') {
                // 二重出勤防止：なければ作成
                Attendance::firstOrCreate(
                    ['user_id' => $userId, 'date' => today()],
                    ['start_time' => now()->format('H:i:s')]
                );
            }

            if ($action === 'end' && $attendance) {
                // 退勤時は休憩状態を解除
                session()->forget('is_break');

                $attendance->update([
                    'end_time' => now()->format('H:i:s'),
                ]);
            }

            if ($action === 'break_start' && $attendance && !$attendance->end_time) {
                session(['is_break' => true]);
            }

            if ($action === 'break_end') {
                session()->forget('is_break');
            }

            return redirect()->route('attendance');
        }

        // ===== GET（表示）=====
        $isBreak = (bool) session('is_break', false);

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
