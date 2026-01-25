@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/detail.css') }}" />
@endsection

@section('header')
@include('partials.user-header')
@endsection

@section('content')


<div class="page">
    <h1 class="page__title">勤怠詳細</h1>

    <form action="" method="POST" class="request-form">
        @csrf
        <div class="card">
            <div class="table">
                <div class="row">
                    <div class="cell head">名前</div>
                    <div class="cell value">
                        <span class="text--bold">{{ $attendance->user->name}}</span>
                    </div>
                </div>

                <div class="row">
                    <div class="cell head">日付</div>
                    <div class="cell value date">
                        <span class="date__item text--bold">{{ $attendance->year_label}}</span>
                        <span class="date__item text--bold">{{ $attendance->md_label}}</span>
                    </div>
                </div>

                <div class="row">
                    <div class="cell head">出勤・退勤</div>
                    <div class="cell value time">
                        <input type="text" class="input input--time" value="{{ $attendance->start_label}}">
                        <span class="time__sep">〜</span>
                        <input type="text" class="input input--time" value="{{ $attendance->end_label}}">
                    </div>
                </div>

                @forelse ($attendance->breaks as $i => $break)
                <div class="row">
                    <div class="cell head">
                        休憩{{ $i === 0 ? '' : $i + 1 }}
                    </div>
                    <div class="cell value time">
                        <input type="text" class="input input--time" value="{{ $break->start_label }}">
                        <span class="time__sep">〜</span>
                        <input type="text" class="input input--time" value="{{ $break->end_label }}">
                    </div>
                </div>
                @empty
                <div class="row">
                    <div class="cell head">休憩</div>
                    <div class="cell value time">
                        <input type="text" class="input input--time" value="">
                        <span class="time__sep">〜</span>
                        <input type="text" class="input input--time" value="">
                    </div>
                </div>
                @endforelse

                <div class="row row--last">
                    <div class="cell head">備考</div>
                    <div class="cell value">
                        <input type="text" class="input input--time" value="{{ old('reason') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn">修正</button>
        </div>
    </form>
</div>
@endsection

