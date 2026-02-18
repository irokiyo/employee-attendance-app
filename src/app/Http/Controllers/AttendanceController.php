<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequestRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // 勤怠一覧画面(管理者)
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
            $attendance->end_label = $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '';
            $attendance->breaks->each(function ($break) {
                $break->start_label = $break->break_start_time
                ? Carbon::parse($break->break_start_time)->format('H:i')
                : '';

                $break->end_label = $break->break_end_time
                ? Carbon::parse($break->break_end_time)->format('H:i')
                : '';
            });
        });

        return view('admin.index', [
            'attendances' => $attendances,
            'currentDate' => $current->format('Y年n月j日'),
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
        ]);

    }

    // 勤怠詳細画面（管理者）
    public function adminDetail($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);
        $date = $attendance->date ? Carbon::parse($attendance->date) : null;
        $attendance->year_label = $date ? $date->format('Y年') : '';
        $attendance->md_label = $date ? $date->format('n月j日') : '';
        $attendance->start_label = $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '';
        $attendance->end_label = $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '';
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

    // 勤怠詳細修正登録（管理者）
    public function adminDetailSave(AttendanceRequestRequest $request, $id)
    {
        $data = $request->validated();
        $attendance = Attendance::with('breaks')->findOrFail($id);
        $start = !empty($data['start_time'])
            ? Carbon::createFromFormat('H:i', $data['start_time'])->format('H:i:s')
            : null;
        $end = !empty($data['end_time'])
            ? Carbon::createFromFormat('H:i', $data['end_time'])->format('H:i:s')
            : null;

        DB::transaction(function () use ($attendance, $data, $start, $end) {

            $attendance->update([
                'start_time' => $start,
                'end_time'   => $end,
            ]);

            $sent = collect($data['breaks'] ?? []);
            $keptIds = [];
            foreach ($sent as $b) {
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if (empty($bs) && empty($be)) continue;

                $breakPayload = [
                    'break_start_time' => !empty($bs) ? Carbon::createFromFormat('H:i', $bs)->format('H:i:s') : null,
                    'break_end_time'   => !empty($be) ? Carbon::createFromFormat('H:i', $be)->format('H:i:s') : null,
                ];

                if (!empty($b['break_id'])) {
                    $attendance->breaks()->where('id', $b['break_id'])->update($breakPayload);
                    $keptIds[] = (int) $b['break_id'];
                }
                else {
                    $new = $attendance->breaks()->create($breakPayload);
                    $keptIds[] = $new->id;
                }
            }

            if (array_key_exists('breaks', $data)) {
                $attendance->breaks()->whereNotIn('id', $keptIds)->delete();
            }

            $freshBreaks = $attendance->breaks()
                ->orderBy('id')
                ->get(['id', 'break_start_time', 'break_end_time'])
                ->map(function ($br) {
                    return [
                        'break_id'         => $br->id,
                        'break_start_time' => $br->break_start_time ? Carbon::parse($br->break_start_time)->format('H:i:s') : null,
                        'break_end_time'   => $br->break_end_time ? Carbon::parse($br->break_end_time)->format('H:i:s') : null,
                    ];
                })->toArray();

            $payload = [
                'start_time' => $start,
                'end_time'   => $end,
                'breaks'     => $freshBreaks,
            ];

            $req = AttendanceRequest::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->first();

            if ($req) {
                $req->update([
                    'status'      => 'approved',
                    'payload'     => $payload,
                    'reason'      => $data['reason'],
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }
            else{
                AttendanceRequest::create([
                    'user_id'       => $attendance->user_id,
                    'attendance_id' => $attendance->id,
                    'break_id'      => null,
                    'status'        => 'approved',
                    'payload'       => $payload,
                    'reason'        => $data['reason'],
                    'reviewed_by'   => auth()->id(),
                    'reviewed_at'   => now(),
                ]);
            }
        });

        return redirect()->route('request.index');
    }


    // 出勤登録画面(一般ユーザー)
    public function userAttendance(Request $request)
    {
        $userId = auth()->id();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();

        $latestBreak = $attendance ? $attendance->breaks()->latest('id')->first() : null;

        $isBreak = $latestBreak && is_null($latestBreak->break_end_time);

        if ($request->isMethod('post')) {
            $action = $request->input('action');

            if ($action === 'start') {
                Attendance::firstOrCreate(
                    ['user_id' => $userId, 'date' => today()],
                    ['start_time' => now()->format('H:i:s')]
                );
            }

            if ($action === 'break_start' && $attendance && ! $attendance->end_time) {
                if (! $isBreak) {
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

            if ($action === 'end' && $attendance && ! $attendance->end_time) {
                if ($isBreak) {
                    $latestBreak->update(['break_end_time' => now()->format('H:i:s')]);
                }
                $attendance->update(['end_time' => now()->format('H:i:s')]);
            }

            return redirect()->route('user.attendance');
        }

        if (! $attendance) {
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

    // 勤怠一覧画面（一般ユーザー)
    public function userIndex(Request $request)
    {
        $userId = auth()->id();

        $month = $request->input('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $start = $current->copy()->startOfMonth();
        $end = $current->copy()->endOfMonth();

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(function ($a) use ($weekdays) {
                $d = Carbon::parse($a->date);
                $a->date_label = $d->format('m/d').'('.$weekdays[$d->dayOfWeek].')';

                $a->start_label = $a->start_time ? Carbon::parse($a->start_time)->format('H:i') : '';
                $a->end_label = $a->end_time ? Carbon::parse($a->end_time)->format('H:i') : '';

                return $a;
            });

        $attendanceByDate = $attendances->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());

        $rows = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            $a = $attendanceByDate->get($dateStr);

            $rows->push([
                'date_label' => $d->format('m/d').'('.$weekdays[$d->dayOfWeek].')',
                'start_label' => $a?->start_time ? Carbon::parse($a->start_time)->format('H:i') : '',
                'end_label' => $a?->end_time ? Carbon::parse($a->end_time)->format('H:i') : '',
                'total_break_time' => $a->total_break_time ?? '',
                'total_time' => $a->total_time ?? '',
                'attendance_id' => $a->id ?? null,
            ]);
        }

        return view('user.index', [
            'rows' => $rows,
            'currentMonthLabel' => $current->format('Y/m'),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }
}
