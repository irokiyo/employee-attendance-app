@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
@endsection

@section('header')
@include('partials.user-header')
@endsection

@section('content')

<div class="attendance">

    {{-- ステータスラベル --}}
    <span class="status-label">
        @if($status === 'outside') 勤務外
        @elseif($status === 'working') 出勤中
        @elseif($status === 'break') 休憩中
        @elseif($status === 'finished') 退勤済
        @endif
    </span>

    <p class="attendance-date">{{ $date }}</p>
    <p class="attendance-time">{{ $time }}</p>

    {{-- ボタン切り替え --}}
    <div class="attendance-actions">

        @if($status === 'outside')
        <button class="btn btn-black">出勤</button>

        @elseif($status === 'working')
        <button class="btn btn-black">退勤</button>
        <button class="btn btn-white">休憩入</button>

        @elseif($status === 'break')
        <button class="btn btn-white">休憩戻</button>

        @elseif($status === 'finished')
        <p class="attendance-message">お疲れ様でした。</p>
        @endif

    </div>
</div>
@endsection

