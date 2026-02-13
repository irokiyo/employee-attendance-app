<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class StaffController extends Controller
{
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

        $attendances = Attendance::query()
            ->where('user_id', $id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $attendanceByDate = $attendances->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());
        $rows = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            $a = $attendanceByDate->get($dateStr);

            $rows->push([
                'date_label' => $d->format('m/d') . '(' . $weekdays[$d->dayOfWeek] . ')',
                'start_label' => $a?->start_time ? Carbon::parse($a->start_time)->format('H:i') : '',
                'end_label'   => $a?->end_time   ? Carbon::parse($a->end_time)->format('H:i')   : '',
                'total_break_time' => $a?->total_break_time ?? '',
                'total_time'       => $a?->total_time ?? '',
                'attendance_id'    => $a?->id ?? null,
            ]);
        }

        return view('admin.detail.staff.show',[
            'rows' => $rows,
            'currentMonthLabel' => $current ->format('Y年n月'),
            'prevMonth' =>$prevMonth,
            'nextMonth' =>$nextMonth,
            'user' => $user
        ]);
    }

}
