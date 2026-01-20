<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'userAttendance'])->name('user.attendance'); //出勤登録画面（一般ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'userIndex'])->name('user.index'); //勤怠一覧画面（一般ユーザー）
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestIndex'])->name('request.index'); //申請一覧画面（管理者）（一般ユーザー）
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'userDetail'])->name('user.detail'); //勤怠詳細画面（一般ユーザー）
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AttendanceController::class, 'adminIndex'])->name('admin.index'); //勤怠一覧画面（管理者）
    Route::get('/admin/attendance/{id}', [AttendanceController::class, 'adminDetail'])->name('admin.detail'); //勤怠詳細画面（管理者）
    Route::get('/admin/staff/list', [AttendanceController::class, 'adminStaffIndex'])->name('admin.staff.index'); //スタッフ一覧画面（管理者）
    Route::get('/admin/attendance/staff/{id}', [AttendanceController::class, 'adminStaffShow'])->name('admin.attendance.show'); //スタッフ別勤怠一覧画面（管理者）
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceController::class, 'adminRequestShow'])->name('admin.request.show'); //修正申請承認画面（管理者）
});



Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', fn () => view('admin.login'))->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('fortify.guard:admin');
});