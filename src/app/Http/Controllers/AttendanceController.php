<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use App\Http\Requests\AttendanceRequestRequest;


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
    //勤怠一覧画面（一般ユーザー)
    public function userIndex(Request $request)
    {
        $userId = auth()->id();

        $month = $request->input('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $start = $current->copy()->startOfMonth();
        $end = $current->copy()->endOfMonth();

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $weekdays = ['日','月','火','水','木','金','土'];

        $attendances = Attendance::query()->where('user_id', $userId)
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(function ($a) use ($weekdays) {
            $d = Carbon::parse($a->date);
            $a->date_label = $d->format('m/d') . '(' . $weekdays[$d->dayOfWeek] . ')';

            $a->start_label = $a->start_time ? Carbon::parse($a->start_time)->format('H:i') : '';
            $a->end_label   = $a->end_time   ? Carbon::parse($a->end_time)->format('H:i')   : '';

            return $a;
            });

        return view('user.index',[
            'attendances' =>$attendances,
            'currentMonthLabel' => $current ->format('Y年n月'),
            'prevMonth' =>$prevMonth,
            'nextMonth' =>$nextMonth,
        ]);
    }
    //勤怠詳細画面（一般ユーザー）
    public function userDetail($id){
        $userId = auth()->id();
        $attendance = Attendance::with('breaks')
            ->where('user_id', $userId)
            ->findOrFail($id);

        $date = $attendance->date ? Carbon::parse($attendance->date) : null;
        $attendance->year_label = $date ? $date->format('Y年') : '';
        $attendance->md_label  = $date ? $date->format('n月j日') : '';
        $attendance->start_label = $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '';
        $attendance->end_label  = $attendance->end_time   ? Carbon::parse($attendance->end_time)->format('H:i')   : '';
        $attendance->breaks->each(function ($break) {
        $break->start_label = $break->break_start_time
            ? Carbon::parse($break->break_start_time)->format('H:i')
            : '';

        $break->end_label = $break->break_end_time
            ? Carbon::parse($break->break_end_time)->format('H:i')
            : '';
        });

        return view('user.detail',compact('attendance'));
    }
    //勤怠詳細の修正登録（一般ユーザー
    public function userRequest(AttendanceRequestRequest $request, $id){
        $data = $request->validated();

        $start = !empty($data['start_time'])
        ? Carbon::createFromFormat('H:i', $data['start_time'])->format('H:i:s')
        : null;
        $end = !empty($data['end_time'])
        ? Carbon::createFromFormat('H:i', $data['end_time'])->format('H:i:s')
        : null;
        $breaksPayload = [];
        if (!empty($data['breaks']) && is_array($data['breaks'])) {
            foreach ($data['breaks'] as $b) {
                $breaksPayload[] = [
                    'break_id' => $b['break_id'] ?? null,
                    'start' => !empty($b['start'])
                        ? Carbon::createFromFormat('H:i', $b['start'])->format('H:i:s')
                        : null,
                    'end' => !empty($b['end'])
                        ? Carbon::createFromFormat('H:i', $b['end'])->format('H:i:s')
                        : null,
                ];
            }
        }

        $payload = [
            'start_time' => $start,
            'end_time'   => $end,
            'breaks'     => $data['breaks'] ?? [],
        ];

        AttendanceRequest::create([
            'user_id' => auth()->id(),
            'attendance_id'=> $id,
            'break_id'=> null,
            'status'=> 'pending',
            'payload'=> $payload,
            'reason' => $request->reason,
            'reviewed_by'=> null,
            'reviewed_at'=> null,
        ]);

        return redirect()->route('request.index');
    }
    //勤怠詳細の修正登録（一般ユーザー
    public function requestIndex(){
        return view('user.request-list');
    }
}
