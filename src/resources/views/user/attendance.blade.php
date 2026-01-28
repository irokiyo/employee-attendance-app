@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
<link rel="stylesheet" href="{{ asset('css/user/attendance.css') }}" />
@endsection

@section('header')
@include('partials.user-header')
@endsection

@section('content')

<form action="{{route('user.attendance')}}" method="POST">
    @csrf
    <div class="attendance">
        <span class="status-label">
            @if($status === 'outside') 勤務外
            @elseif($status === 'working') 出勤中
            @elseif($status === 'break') 休憩中
            @elseif($status === 'finished') 退勤済
            @endif
        </span>

        <p class="attendance-date">{{ $date }}</p>
        <p class="attendance-time">{{ $time }}</p>

        <div class="attendance-actions">

            @if($status === 'outside')
            <button type="submit" name="action" value="start" class="attendance__btn">出勤</button>

            @elseif($status === 'working')
            <button type="submit" name="action" value="end" class="attendance__btn">退勤</button>
            <button type="submit" name="action" value="break_start" class="break__btn">休憩入</button>

            @elseif($status === 'break')
            <button type="submit" name="action" value="break_end" class="break__btn">休憩戻</button>

            @elseif($status === 'finished')
            <p class="attendance-message">お疲れ様でした。</p>
            @endif

        </div>
    </div>
</form>
@endsection

