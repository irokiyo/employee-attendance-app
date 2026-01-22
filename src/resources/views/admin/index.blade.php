@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}" />
@endsection

@section('header')
@include('partials.header')
@endsection

@section('content')
<div class="attendance-page">
    <div class="attendance-page__inner">

        <h1 class="page__title">
            {{ $titleDate }}の勤怠
        </h1>

        <div class="date-menu">
            <a class="previous-day" href="{{ route('admin.index', ['date' => $prevDate]) }}"><img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow"></a>
            <p class="date-character">前日</p>

            <div class="select-date">
                <input class="date-nav__icon" aria-hidden="true"><img src="{{ asset('/images/左矢印.png') }}" alt="カレンダー" class="calendar"></input>
                <span class="date-character">{{ $displayDate }}</span>
            </div>

            <a class="next-day" href="{{ route('admin.index', ['date' => $nextDate]) }}"><img src="{{ asset('/images/左矢印.png') }}" alt="矢印" class="arrow"></a>
            <p class="date-character">翌日</p>
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

                <tr class="table__row">
                @foreach($attendances as $attendance)
                    <td class="table__item">{{ $attendance->use->name }}</td>
                    <td class="table__item">{{ $attendance->start }}</td>
                    <td class="table__item">{{ $attendance->end }}</td>
                    <td class="table__item">{{ $attendance->break }}</td>
                    <td class="table__item">{{ $attendance->total }}</td>
                    <td class="table__item">
                        <a class="detail-link" href="{{ route('admin.detail', ['id' => $attendance->id]) }}">
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

