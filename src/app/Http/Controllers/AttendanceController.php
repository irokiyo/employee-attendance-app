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
    public function adminIndex(Request $request)
    {
        $day = $request->input('date', now()->toDateString());
        $current = Carbon::createFromFormat('Y-m-d', $day);

        $prevDate = $current->copy()->subDay()->format('Y-m-d');
        $nextDate = $current->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::query()
            ->with(['user', 'breaks'])
            ->whereDate('date', $current->toDateString())
            ->orderBy('user_id')
            ->get();
        $attendances->each(function ($attendance) {
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
        });

        return view('admin.index',[
            'attendances' =>$attendances,
            'currentDate' => $current->format('Y年n月j日'),
            'prevDate' =>$prevDate,
            'nextDate' =>$nextDate,
        ]);

    }
    //勤怠詳細画面（管理者）
    public function adminDetail($id){
        $attendance = Attendance::with(['user','breaks'])->findOrFail($id);

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

        return view('admin.detail.show', compact('attendance'));
    }
    //勤怠詳細修正登録（管理者）
    public function adminDetailSave(AttendanceRequestRequest $request, $id){
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
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if (empty($bs) && empty($be)) {
                    continue;
                }

                $breaksPayload[] = [
                    'break_id' => $b['break_id'] ?? null,
                    'break_start_time' => !empty($bs)
                        ? Carbon::createFromFormat('H:i', $bs)->format('H:i:s')
                        : null,
                    'break_end_time' => !empty($be)
                        ? Carbon::createFromFormat('H:i', $be)->format('H:i:s')
                        : null,
                ];
            }
        }

        $payload = [
            'start_time' => $start,
            'end_time'   => $end,
            'breaks'     => $breaksPayload,
        ];

        AttendanceRequest::create([
            'user_id' => auth()->id(),
            'attendance_id'=> $id,
            'break_id'=> null,
            'status'=> 'pending',
            'payload'=> $payload,
            'reason' => $data['reason'],
            'reviewed_by'=> null,
            'reviewed_at'=> null,
        ]);

        return redirect()->route('request.index');
    }

    //スタッフ一覧画面（管理者）
    public function adminStaffIndex(){

        $users = User::query()->get();

        return view('admin.detail.staff.index',compact('users'));
    }
    //スタッフ別勤怠一覧画面（管理者）
    public function adminStaffShow(Request $request,$id){

        $user = User::findOrFail($id);
        $month = $request->input('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $start = $current->copy()->startOfMonth();
        $end = $current->copy()->endOfMonth();

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $weekdays = ['日','月','火','水','木','金','土'];

        $attendances = Attendance::query()->where('user_id', $id)
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

        return view('admin.detail.staff.show',[
            'attendances' =>$attendances,
            'currentMonthLabel' => $current ->format('Y年n月'),
            'prevMonth' =>$prevMonth,
            'nextMonth' =>$nextMonth,
            'user' => $user
        ]);
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
            'date' => Carbon::today()->locale('ja')->isoFormat('YYYY年M月D日(ddd)'),
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

        $attendances = Attendance::query()
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

        $attendanceByDate = $attendances->keyBy(fn($a) => Carbon::parse($a->date)->toDateString());

        $rows = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            $a = $attendanceByDate->get($dateStr);

            $rows->push([
                'date_label' => $d->format('m/d') . '(' . $weekdays[$d->dayOfWeek] . ')',
                'start_label' => $a?->start_time ? Carbon::parse($a->start_time)->format('H:i') : '',
                'end_label'   => $a?->end_time   ? Carbon::parse($a->end_time)->format('H:i')   : '',
                'total_break_time' => $a->total_break_time ?? '',
                'total_time' => $a->total_time ?? '',
                'attendance_id' => $a->id ?? null,
            ]);
        }

        return view('user.index',[
            'rows' => $rows,
            'currentMonthLabel' => $current ->format('Y/m'),
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

        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)
        ->where('user_id', $userId)
        ->latest()
        ->first();

        $isPending = $attendanceRequest && $attendanceRequest->status === 'pending';

        $payload = $attendanceRequest->payload ?? [];
            $reqStart = $payload['start_time'] ?? '';
            $reqEnd   = $payload['end_time'] ?? '';
        
        $reqStart = !empty($payload['start_time'])
            ? Carbon::parse($payload['start_time'])->format('H:i')
            : '';

        $reqEnd = !empty($payload['end_time'])
            ? Carbon::parse($payload['end_time'])->format('H:i')
            : '';

        $rawBreaks = [];
            if (isset($payload['break']) && is_array($payload['break'])) {
                $rawBreaks = [$payload['break']];
            }
            elseif (isset($payload['breaks']) && is_array($payload['breaks'])) {
                $rawBreaks = $payload['breaks'];
            }
        $reqBreaks = collect($rawBreaks)->map(function ($break) {
            return [
                'break_start_time' => !empty($break['break_start_time'])
                ? Carbon::parse($break['break_start_time'])->format('H:i')
                : '',
                'break_end_time' => !empty($break['break_end_time'])
                ? Carbon::parse($break['break_end_time'])->format('H:i')
                : '',
            ];
        })->toArray();

        return view('user.detail', compact(
            'attendance',
            'attendanceRequest',
            'isPending',
            'reqStart',
            'reqEnd',
            'reqBreaks'
        ));
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
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if (empty($bs) && empty($be)) {
                    continue;
                }

                $breaksPayload[] = [
                    'break_id' => $b['break_id'] ?? null,
                    'break_start_time' => !empty($bs)
                        ? Carbon::createFromFormat('H:i', $bs)->format('H:i:s')
                        : null,
                    'break_end_time' => !empty($be)
                        ? Carbon::createFromFormat('H:i', $be)->format('H:i:s')
                        : null,
                ];
            }
        }

        $payload = [
            'start_time' => $start,
            'end_time'   => $end,
            'breaks'     => $breaksPayload,
        ];

        AttendanceRequest::create([
            'user_id' => auth()->id(),
            'attendance_id'=> $id,
            'break_id'=> null,
            'status'=> 'pending',
            'payload'=> $payload,
            'reason' => $data['reason'],
            'reviewed_by'=> null,
            'reviewed_at'=> null,
        ]);

        return redirect()->route('request.index');
    }
    //申請一覧画面（管理者）（一般ユーザー）
    public function requestIndex(Request $request){
        $user = auth()->user();
        $status = $request->query('status', 'pending');
        $query = AttendanceRequest::with(['user', 'attendance'])
            ->latest();

        if ($user->status =='user'){
            $query->where('user_id', $user->id);
        }

        $query->where('status', $status);
        $reqs = $query->get();

        $reqs->transform(function ($r) {
            $r->status_label = match ($r->status) {
                'pending'  => '承認待ち',
                'approved' => '承認済み',
                'rejected' => '却下',
                default    => '不明',
            };
                $r->attendance_time = $r->attendance->date ? Carbon::parse($r->attendance->date)->format('Y/m/d') : '';
                $r->request_time = $r->created_at ? Carbon::parse($r->created_at)->format('Y/m/d') : '';

                return $r;
        });

        if ($user->status === 'admin') {
            return view('admin.detail.request.index', compact('reqs', 'status'));
        }

            return view('user.request-list',compact('reqs', 'status'));
    }
}
