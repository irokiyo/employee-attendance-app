@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}" />
@endsection

@section('header')
@include('partials.user-header')
@endsection

@section('content')
<div class="attendance-list">
    <div class="page">
        <h2 class="page__title">勤怠詳細</h2>

        <form action="{{ route('user.request.date', ['date' => $targetDate->format('Y-m-d')]) }}" method="POST" class="request-form">
            @csrf

            <div class="attendance-table">
                <div class="table">

                    <div class="row">
                        <div class="row__head">名前</div>
                        <div class="cell value">
                            <span class="text--bold">{{ auth()->user()->name }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row__head">日付</div>
                        <div class="cell value date">
                            <span class="date__item text--bold">{{ $year_label }}</span>
                            <span class="date__item text--bold">{{ $md_label }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row__head">出勤・退勤</div>
                        <div class="cell value time">
                            <input type="text" class="input input--time" name="start_time" value="">
                            <span class="time__sep">〜</span>
                            <input type="text" class="input input--time" name="end_time" value="">
                        </div>
                        @error('start_time') <p class="error-message">{{ $message }}</p> @enderror
                    </div>

                    @foreach($displayBreaks as $i => $b)
                    <div class="row">
                        <div class="row__head">休憩{{ $i === 0 ? '' : $i + 1 }}</div>
                        <div class="cell value time">
                            <input type="text" class="input input--time" name="breaks[{{ $i }}][break_start_time]" value="">
                            <span class="time__sep">〜</span>
                            <input type="text" class="input input--time" name="breaks[{{ $i }}][break_end_time]" value="">
                        </div>
                        @error("breaks.$i.break_start_time") <p class="error-message">{{ $message }}</p> @enderror
                        @error("breaks.$i.break_end_time") <p class="error-message">{{ $message }}</p> @enderror
                    </div>
                    @endforeach

                    <div class="row">
                        <div class="row__head">備考</div>
                        <div class="cell value">
                            <input type="text" class="input" name="reason" value="{{ old('reason') }}">
                        </div>
                        @error('reason')
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="actions">
                @if($isPending)
                <p class="error-message">*承認待ちのため修正はできません。</p>
                @else
                <button type="submit" class="btn">修正</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
