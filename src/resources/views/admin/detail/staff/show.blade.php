@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}" />
@endsection

@section('header')
@include('partials.admin-header')
@endsection

@section('content')
<div class="attendance-list">
    <div class="attendance-page__inner">

        <h1 class="page__title">{{ $user->name }}さんの勤怠
        </h1>

        <div class="attendance-nav">
            <div class="prev-month">
                <a class="month__btn" href="{{ route('admin.attendance.show', ['id' => $user->id,'month' => $prevMonth]) }}">
                    <img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow">
                </a>
                <p class="date-character">前月</p>
            </div>

            <div class="month-nav__center">
                <form action="{{ route('admin.attendance.show',['id' => $user->id]) }}" method="GET" class="month-form">
                    <label class="month-picker">
                        <img src="{{ asset('/images/カレンダー.png') }}" alt="カレンダー" class="calendar">
                        <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="month-input" onchange="this.form.submit()">
                    </label>
                </form>
                <p class="attendance-nav__label">{{ $currentMonthLabel }}</p>
            </div>

            <div class="next-month">
                <p class="date-character">翌月</p>
                <a class="month__btn" href="{{ route('admin.attendance.show', ['id' => $user->id,'month' => $nextMonth]) }}">
                    <img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow-right">
                </a>
            </div>
        </div>

        <div class="attendance-table">
            <table class="table">
                <tr class="table__row">
                    <th class="table__header">日付</th>
                    <th class="table__header">出勤</th>
                    <th class="table__header">退勤</th>
                    <th class="table__header">休憩</th>
                    <th class="table__header">合計</th>
                    <th class="table__header">詳細</th>
                </tr>

                @foreach($rows as $row)
                <tr>
                    <td class="table__item">{{ $row['date_label']}}</td>
                    <td class="table__item">{{ $row['start_label']}}</td>
                    <td class="table__item">{{ $row['end_label']}}</td>
                    <td class="table__item">{{ $row['total_break_time'] }}</td>
                    <td class="table__item">{{ $row['total_time']}}</td>
                    <td class="table__item">
                        @if($row['attendance_id'])
                        <a class="detail-link" href="{{route('admin.detail',['id' => $row['attendance_id']])}}">
                            詳細
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('admin.attendance.csv', ['id' => $user->id, 'month' => request('month', now()->format('Y-m'))]) }}">
                CSV出力
            </a>
        </div>
    </div>
</div>


@endsection

