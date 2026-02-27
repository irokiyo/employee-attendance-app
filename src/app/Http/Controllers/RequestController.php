<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequestRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    // 申請一覧画面（管理者）（一般ユーザー）
    public function requestIndex(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status', 'pending');
        $query = AttendanceRequest::with(['user', 'attendance'])
            ->latest();

        if ($user->status == 'user') {
            $query->where('user_id', $user->id);
        }

        $query->where('status', $status);
        $reqs = $query->get();

        $reqs->transform(function ($r) {
            $r->status_label = match ($r->status) {
                'pending' => '承認待ち',
                'approved' => '承認済み',
                'rejected' => '却下',
                default => '不明',
            };
            $r->attendance_time = $r->attendance->date ? Carbon::parse($r->attendance->date)->format('Y/m/d') : '';
            $r->request_time = $r->created_at ? Carbon::parse($r->created_at)->format('Y/m/d') : '';

            return $r;
        });

        if ($user->status === 'admin') {
            return view('admin.detail.request.index', compact('reqs', 'status'));
        }

        return view('user.request-list', compact('reqs', 'status'));
    }

    // 勤怠詳細画面（一般ユーザー）
    public function userDetail($id)
    {
        $userId = auth()->id();
        $attendance = Attendance::with('breaks')
            ->where('user_id', $userId)
            ->findOrFail($id);
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

        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        $isPending = $attendanceRequest && $attendanceRequest->status === 'pending';

        $payload = $attendanceRequest->payload ?? [];
        $reqStart = $payload['start_time'] ?? '';
        $reqEnd = $payload['end_time'] ?? '';

        $reqStart = ! empty($payload['start_time'])
            ? Carbon::parse($payload['start_time'])->format('H:i')
            : '';

        $reqEnd = ! empty($payload['end_time'])
            ? Carbon::parse($payload['end_time'])->format('H:i')
            : '';

        $rawBreaks = [];
        if (isset($payload['break']) && is_array($payload['break'])) {
            $rawBreaks = [$payload['break']];
        } elseif (isset($payload['breaks']) && is_array($payload['breaks'])) {
            $rawBreaks = $payload['breaks'];
        }
        $reqBreaks = collect($rawBreaks)->map(function ($break) {
            return [
                'break_start_time' => ! empty($break['break_start_time'])
                ? Carbon::parse($break['break_start_time'])->format('H:i')
                : '',
                'break_end_time' => ! empty($break['break_end_time'])
                ? Carbon::parse($break['break_end_time'])->format('H:i')
                : '',
            ];
        })->toArray();

        $breaks = $attendance->breaks ?? collect();
        $oldBreaks = old('breaks', []);
        $filledOldCount = 0;
        if (is_array($oldBreaks)) {
            $filtered = array_values(array_filter($oldBreaks, function ($b) {
            $s = $b['break_start_time'] ?? '';
            $e = $b['break_end_time'] ?? '';
            return trim((string)$s) !== '' || trim((string)$e) !== '';
            }));
            $filledOldCount = count($filtered);
        }
        $existingCount = $breaks->count();
        $oldCount = is_array($oldBreaks) ? count($oldBreaks) : 0;
        $displayCount = max($breaks->count(), $filledOldCount) + 1;

        return view('user.detail', compact(
            'attendance',
            'attendanceRequest',
            'isPending',
            'reqStart',
            'reqEnd',
            'reqBreaks',
            'breaks',
            'existingCount',
            'displayCount',
            'filledOldCount'
        ));
    }

    // 勤怠詳細の修正登録（一般ユーザー
    public function userRequest(AttendanceRequestRequest $request, $id)
    {
        $data = $request->validated();

        $start = ! empty($data['start_time'])
        ? Carbon::createFromFormat('H:i', $data['start_time'])->format('H:i:s')
        : null;
        $end = ! empty($data['end_time'])
        ? Carbon::createFromFormat('H:i', $data['end_time'])->format('H:i:s')
        : null;
        $breaksPayload = [];
        if (! empty($data['breaks']) && is_array($data['breaks'])) {
            foreach ($data['breaks'] as $b) {
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if (empty($bs) && empty($be)) {
                    continue;
                }

                $breaksPayload[] = [
                    'break_id' => $b['break_id'] ?? null,
                    'break_start_time' => ! empty($bs)
                        ? Carbon::createFromFormat('H:i', $bs)->format('H:i:s')
                        : null,
                    'break_end_time' => ! empty($be)
                        ? Carbon::createFromFormat('H:i', $be)->format('H:i:s')
                        : null,
                ];
            }
        }

        $payload = [
            'start_time' => $start,
            'end_time' => $end,
            'breaks' => $breaksPayload,
        ];

        AttendanceRequest::create([
            'user_id' => auth()->id(),
            'attendance_id' => $id,
            'break_id' => null,
            'status' => 'pending',
            'payload' => $payload,
            'reason' => $data['reason'],
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return redirect()->route('request.index');
    }

    // 修正申請承認画面（管理者）
    public function adminRequestShow(Request $request, $attendance_correct_request_id)
    {
        $attendanceRequest = AttendanceRequest::with(['user', 'attendance.breaks'])
            ->findOrFail($attendance_correct_request_id);
        $attendance = $attendanceRequest->attendance;

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

        $isPending = $attendanceRequest && $attendanceRequest->status === 'pending';

        $payload = $attendanceRequest->payload ?? [];
        $reqStart = $payload['start_time'] ?? '';
        $reqEnd = $payload['end_time'] ?? '';

        $reqStart = ! empty($payload['start_time'])
            ? Carbon::parse($payload['start_time'])->format('H:i')
            : '';

        $reqEnd = ! empty($payload['end_time'])
            ? Carbon::parse($payload['end_time'])->format('H:i')
            : '';

        $rawBreaks = [];
        if (isset($payload['break']) && is_array($payload['break'])) {
            $rawBreaks = [$payload['break']];
        } elseif (isset($payload['breaks']) && is_array($payload['breaks'])) {
            $rawBreaks = $payload['breaks'];
        }
        $reqBreaks = collect($rawBreaks)->map(function ($break) {
            return [
                'break_start_time' => ! empty($break['break_start_time'])
                ? Carbon::parse($break['break_start_time'])->format('H:i')
                : '',
                'break_end_time' => ! empty($break['break_end_time'])
                ? Carbon::parse($break['break_end_time'])->format('H:i')
                : '',
            ];
        })->toArray();
        $displayBreaks = collect($reqBreaks ?? [])
        ->filter(function ($b) {
            $s = $b['break_start_time'] ?? '';
            $e = $b['break_end_time'] ?? '';
            return trim((string)$s) !== '' || trim((string)$e) !== '';
        })
        ->values();


        return view('admin.detail.request.show', compact(
            'attendance',
            'attendanceRequest',
            'isPending',
            'reqStart',
            'reqEnd',
            'reqBreaks',
            'displayBreaks'
        ));
    }

    // 修正申請更新（管理者）
    public function adminRequestUpdate(Request $request, $attendance_correct_request_id)
    {
        $reviewedBy = auth()->id();
        $reviewedAt = now();
        $req = AttendanceRequest::with(['attendance', 'attendance.breaks'])->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($req, $reviewedBy, $reviewedAt) {

            $req->update([
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedAt,
            ]);

            $payload = $req->payload ?? [];
            $req->attendance->update([
                'start_time' => $payload['start_time'] ?? $req->attendance->start_time,
                'end_time' => $payload['end_time'] ?? $req->attendance->end_time,
            ]);
            if (! empty($payload['breaks']) && is_array($payload['breaks'])) {
                $req->attendance->breaks()->delete();

                foreach ($payload['breaks'] as $b) {
                    $req->attendance->breaks()->create([
                        'break_start_time' => $b['break_start_time'] ?? null,
                        'break_end_time' => $b['break_end_time'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('request.index');
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
        $breaks = $attendance->breaks ?? collect();
        $oldBreaks = old('breaks', []);
        $filledOldCount = 0;
        if (is_array($oldBreaks)) {
            $filtered = array_values(array_filter($oldBreaks, function ($b) {
            $s = $b['break_start_time'] ?? '';
            $e = $b['break_end_time'] ?? '';
            return trim((string)$s) !== '' || trim((string)$e) !== '';
            }));
            $filledOldCount = count($filtered);
        }
        $displayCount = max($breaks->count(), $filledOldCount) + 1;
        $maxBreaks = 5;
        $displayCount = min($displayCount, $maxBreaks);

        return view('admin.detail.show', compact(
            'attendance',
            'breaks',
            'displayCount'
        ));
    }

    // 勤怠詳細修正登録（管理者）
    public function adminDetailSave(AttendanceRequestRequest $request, $id)
    {
        $data = $request->validated();
        $attendance = Attendance::with('breaks')->findOrFail($id);
        $start = ! empty($data['start_time'])
            ? Carbon::createFromFormat('H:i', $data['start_time'])->format('H:i:s')
            : null;
        $end = ! empty($data['end_time'])
            ? Carbon::createFromFormat('H:i', $data['end_time'])->format('H:i:s')
            : null;
        DB::transaction(function () use ($attendance, $data, $start, $end) {
            $attendance->update([
                'start_time' => $start,
                'end_time' => $end,
            ]);
            $sent = collect($data['breaks'] ?? []);
            $keptIds = [];
            foreach ($sent as $b) {
                $bs = $b['break_start_time'] ?? null;
                $be = $b['break_end_time'] ?? null;

                if (empty($bs) && empty($be)) {
                    continue;
                }

                $breakPayload = [
                    'break_start_time' => ! empty($bs) ? Carbon::createFromFormat('H:i', $bs)->format('H:i:s') : null,
                    'break_end_time' => ! empty($be) ? Carbon::createFromFormat('H:i', $be)->format('H:i:s') : null,
                ];

                if (! empty($b['break_id'])) {
                    $attendance->breaks()->where('id', $b['break_id'])->update($breakPayload);
                    $keptIds[] = (int) $b['break_id'];
                } else {
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
                        'break_id' => $br->id,
                        'break_start_time'
                            => $br->break_start_time ? Carbon::parse($br->break_start_time)
                            ->format('H:i:s') : null,
                        'break_end_time'
                            => $br->break_end_time ? Carbon::parse($br->break_end_time)
                            ->format('H:i:s') : null,
                    ];
                })->toArray();
            $payload = [
                'start_time' => $start,
                'end_time' => $end,
                'breaks' => $freshBreaks,
            ];
            $req = AttendanceRequest::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->first();
            if ($req) {
                $req->update([
                    'status' => 'approved',
                    'payload' => $payload,
                    'reason' => $data['reason'],
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            } else {
                AttendanceRequest::create([
                    'user_id' => $attendance->user_id,
                    'attendance_id' => $attendance->id,
                    'break_id' => null,
                    'status' => 'approved',
                    'payload' => $payload,
                    'reason' => $data['reason'],
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }
        });

        return redirect()->route('request.index');
    }
}
