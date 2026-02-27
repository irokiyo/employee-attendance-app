@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}" />
@endsection

@section('header')
@include('partials.admin-header')
@endsection

@section('content')

<div class="attendance-list">
    <div class="page">
        <h1 class="page__title">勤怠詳細</h1>

        <form action="{{route('request.update',['attendance_correct_request_id' => $attendanceRequest->id])}}" method="POST" class="request-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            <div class="attendance-table">
                <div class="table">
                    <div class="row">
                        <div class="row__head">名前</div>
                        <div class="cell value">
                            <span class="text--bold">{{ $attendance->user->name}}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row__head">日付</div>
                        <div class="cell value date">
                            <span class="date__item text--bold">{{ $attendance->year_label}}</span>
                            <span class="date__item text--bold">{{ $attendance->md_label}}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row__head">出勤・退勤</div>
                        <div class="cell value time">
                            <input type="hidden" name="start_time" value="{{$reqStart}}">
                            <p class="time-character">{{$reqStart}}</p>
                            <span class="time__sep">〜</span>
                            <input type="hidden" name="end_time" value="{{$reqEnd}}">
                            <p class="time-character">{{$reqEnd}}</p>
                        </div>
                    </div>

                    @forelse ($displayBreaks as $i => $b)
                    <div class="row">
                        <div class="row__head">
                            {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                        </div>
                        <div class="cell value time">
                            <input type="hidden" name="breaks[{{ $i }}][break_start_time]" value="{{ $b['break_start_time'] ?? '' }}">
                            <p class="time-character">{{ $b['break_start_time'] ?? '' }}</p>

                            <span class="time__sep">〜</span>

                            <input type="hidden" name="breaks[{{ $i }}][break_end_time]" value="{{ $b['break_end_time'] ?? '' }}">
                            <p class="time-character">{{ $b['break_end_time'] ?? '' }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="row">
                        <div class="row__head">休憩</div>
                        <div class="cell value time">
                            <p class="time-character"></p>
                            <span class="time__sep">〜</span>
                            <p class="time-character"></p>
                        </div>
                    </div>
                    @endforelse


                    <div class="row row--last">
                        <div class="row__head">備考</div>
                        <div class="cell value">
                            <input type="hidden" value="{{$attendanceRequest->reason ?? '' }}">
                            <p class="reason-character">{{$attendanceRequest->reason ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="actions">
                @if($attendanceRequest->status=='pending')
                <button type="submit" class="btn">承認</button>
                @else
                <button type="button" class="btn-gray">承認済み</button>
                @endif
            </div>
    </div>
    </form>
</div>
</div>
@endsection

