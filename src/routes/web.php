<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::middleware(['auth', 'verified'])->group(function () {
    // 出勤登録画面（一般ユーザー）
    Route::get('/attendance', [AttendanceController::class, 'userAttendance'])->name('user.attendance');
    // 出勤登録（一般ユーザー）
    Route::post('/attendance', [AttendanceController::class, 'userAttendance'])->name('user.attendance');
    // 勤怠一覧画面（一般ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'userIndex'])->name('user.index');

    // 申請一覧画面（管理者）（一般ユーザー）
    Route::get('/stamp_correction_request/list', [RequestController::class, 'requestIndex'])->name('request.index');
    // 勤怠詳細画面（一般ユーザー）
    Route::get('/attendance/detail/{id}', [RequestController::class, 'userDetail'])->name('user.detail');
    // 勤怠詳細の修正登録（一般ユーザー）
    Route::post('/attendance/detail/{id}', [RequestController::class, 'userRequest'])->name('user.request');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    // 勤怠一覧画面（管理者）
    Route::get('/admin/attendance/list', [AttendanceController::class, 'adminIndex'])->name('admin.index');
    // 勤怠詳細画面（管理者）
    Route::get('/admin/attendance/{id}', [AttendanceController::class, 'adminDetail'])->name('admin.detail');
    // 勤怠詳細修正登録（管理者）
    Route::post('/admin/attendance/{id}', [AttendanceController::class, 'adminDetailSave'])->name('admin.detail.save');

    // スタッフ一覧画面（管理者）
    Route::get('/admin/staff/list', [StaffController::class, 'adminStaffIndex'])->name('admin.staff.index');
    // スタッフ別勤怠一覧画面（管理者）
    Route::get('/admin/attendance/staff/{id}', [StaffController::class, 'adminStaffShow'])->name('admin.attendance.show');
    // スタッフCSV出力（管理者）
    Route::get('/admin/attendance/staff/{id}/csv', [StaffController::class, 'exportCsv'])->name('admin.attendance.csv');

    // 修正申請承認画面（管理者）
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [RequestController::class, 'adminRequestShow'])->name('admin.request.show');
    // 修正申請更新（管理者）
    Route::patch('/stamp_correction_request/approve/{attendance_correct_request_id}', [RequestController::class, 'adminRequestUpdate'])->name('request.update');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('user.auth.login'))->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', fn () => view('admin.login'))->name('admin.login');
});

Route::get('/', function () {
    return redirect()->route('login');
});
