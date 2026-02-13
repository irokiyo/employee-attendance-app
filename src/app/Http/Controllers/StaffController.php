<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Symfony\Component\HttpFoundation\StreamedResponse;


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

    //スタッフCSV出力（管理者）
    public function exportCsv(Request $request, $id){
        $user = User::findOrFail($id);
        $month = $request->input('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();
        $weekdays = ['日','月','火','水','木','金','土'];
        $attendances = Attendance::query()
        ->where('user_id', $id)
        ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
        ->orderBy('date')
        ->get();
        $attendanceByDate = $attendances->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());

        $filename = "勤怠詳細_{$user->name}さん_{$month}月分分.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        return response()->streamDownload(function () use ($start, $end, $attendanceByDate, $weekdays) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->toDateString();
                $a = $attendanceByDate->get($dateStr);
                $dateLabel  = $d->format('m/d') . '(' . $weekdays[$d->dayOfWeek] . ')';
                $startLabel = $a?->start_time ? Carbon::parse($a->start_time)->format('H:i') : '';
                $endLabel   = $a?->end_time   ? Carbon::parse($a->end_time)->format('H:i') : '';

                fputcsv($out, [
                    $dateLabel,
                    $startLabel,
                    $endLabel,
                    $a?->total_break_time ?? '',
                    $a?->total_time ?? '',
                ]);
            }
            fclose($out);
        }, $filename, $headers);
    }
}
