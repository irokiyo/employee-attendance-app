@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/detail.css') }}" />
@endsection

@section('header')
@include('partials.admin-header')
@endsection

@section('content')

<div class="attendance-list">
    <div class="page">
        <h1 class="page__title">勤怠詳細</h1>

        <form action="{{route('admin.detail.save',['id' => $attendance->id])}}" method="POST" class="request-form">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            <div class="card">
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
                            <input type="text" class="input input--time" name="start_time" value="{{ $attendance->start_label}}">
                            <span class="time__sep">〜</span>
                            <input type="text" class="input input--time" name="end_time" value="{{ $attendance->end_label}}">
                        </div>
                        @error('start_time') <p class="error-message">{{ $message }}</p> @enderror
                    </div>

                    @forelse ($attendance->breaks as $i => $break)
                    <div class="row">
                        <div class="row__head">
                            休憩{{ $i === 0 ? '' : $i + 1 }}
                        </div>
                        <div class="cell value time">
                            <input type="hidden" name="breaks[{{ $i }}][break_id]" value="{{ $break->id }}">
                            <input type="text" class="input input--time" name="breaks[{{ $i }}][break_start_time]" value="{{ $break->start_label }}">
                            <span class="time__sep">〜</span>
                            <input type="text" class="input input--time" name="breaks[{{ $i }}][break_end_time]" value="{{ $break->end_label }}">
                        </div>
                        @error("breaks.$i.break_start_time") <p class="error-message">{{ $message }}</p> @enderror
                    </div>

                    @empty
                    <div class="row">
                        <div class="row__head">休憩</div>
                        <div class="cell value time">
                            <input type="text" class="input input--time" value="">
                            <span class="time__sep">〜</span>
                            <input type="text" class="input input--time" value="">
                        </div>
                        @error('reason')
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforelse

                    <div class="row row--last">
                        <div class="row__head">備考</div>
                        <div class="cell value">
                            <input type="text" class="input" name="reason" , value="{{ old('reason') }}">
                        </div>
                        @error('reason')
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn">修正</button>
            </div>
        </form>
    </div>
</div>
@endsection

