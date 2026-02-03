@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}" />
@endsection

@section('header')
@include('partials.admin-header')
@endsection

@section('content')
<div class="attendance-list">
    <div class="attendance-list__inner">

        <h1 class="page__title">
            {{ $currentDate }}の勤怠
        </h1>

        <div class="attendance-nav">
            <div class="prev-month">
                <a class="month__btn" href="{{ route('admin.index', ['date' => $prevDate]) }}">
                    <img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow">
                </a>
                <p class="date-character">前日</p>
            </div>

            <div class="month-nav__center">
                <form action="{{ route('admin.index') }}" method="GET" class="month-form">
                    <label class="month-picker">
                        <img src="{{ asset('/images/カレンダー.png') }}" alt="カレンダー" class="calendar">
                        <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="month-input" onchange="this.form.submit()">
                    </label>
                </form>
                <p class="attendance-nav__label">{{ $currentDate }}</p>
            </div>

            <div class="next-month">
                <p class="date-character">翌日</p>
                <a class="month__btn" href="{{ route('admin.index', ['date' => $nextDate]) }}"><img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow-right">
                </a>
            </div>
        </div>

        <div class="attendance-table">
            <table class="table">
                <tr class="table__row">
                    <th class="table__header">名前</th>
                    <th class="table__header">出勤</th>
                    <th class="table__header">退勤</th>
                    <th class="table__header">休憩</th>
                    <th class="table__header">合計</th>
                    <th class="table__header">詳細</th>
                </tr>

                @foreach($attendances as $attendance)
                <tr>
                    <td class="table__item">{{ $attendance->user->name }}</td>
                    <td class="table__item">{{ $attendance->start_label }}</td>
                    <td class="table__item">{{ $attendance->end_label }}</td>
                    <td class="table__item">{{ $attendance->total_break_time ?? '' }}</td>
                    <td class="table__item">{{ $attendance->total_time }}</td>
                    <td class="table__item">
                        <a class="detail-link" href="{{route('admin.detail',['id' => $attendance->id])}}">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection

