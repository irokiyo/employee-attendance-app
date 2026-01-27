@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}" />
@endsection

@section('header')
@include('partials.user-header')
@endsection

@section('content')
<div class="attendance-list">
    <div class="attendance-list__inner">

        <h2 class="page__title">勤怠一覧</h2>

        <div class="attendance-nav">
            <div class="prev-month">
                <a class="month__btn" href="{{ route('user.index', ['month' => $prevMonth]) }}">
                    <img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow">
                </a>
                <p class="date-character">前月</p>
            </div>

            <div class="month-nav__center">
                <form action="{{ route('user.index') }}" method="GET" class="month-form">
                    <label class="month-picker">
                        <img src="{{ asset('/images/カレンダー.png') }}" alt="カレンダー" class="calendar">
                        <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="month-input" onchange="this.form.submit()">
                    </label>
                </form>
                <p class="attendance-nav__label">{{ $currentMonthLabel }}</p>
            </div>

            <div class="next-month">
                <p class="date-character">翌月</p>
                <a class="month__btn" href="{{ route('user.index', ['month' => $nextMonth]) }}">
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

                @foreach($attendances as $attendance)
                <tr>
                    <td class="table__item">{{ $attendance->date_label}}</td>
                    <td class="table__item">{{ $attendance->start_label}}</td>
                    <td class="table__item">{{ $attendance->end_label}}</td>
                    <td class="table__item">{{ $attendance->total_break_time ?? '' }}</td>
                    <td class="table__item">{{ $attendance->total_time}}</td>
                    <td class="table__item">
                        <a class="detail-link" href="{{route('user.detail',['id' => $attendance->id])}}">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

