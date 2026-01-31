@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/request.css') }}" />
@endsection

@section('header')
@include('partials.user-header')
@endsection

@section('content')
<div class="attendance-list">
    <div class="attendance-list__inner">

        <h2 class="page__title">申請一覧</h2>

        <div class="attendance-table">
            <table class="table">
                <tr class="table__row">
                    <th class="table__header">状態</th>
                    <th class="table__header">名前</th>
                    <th class="table__header">対象日時</th>
                    <th class="table__header">申請理由</th>
                    <th class="table__header">申請日時</th>
                    <th class="table__header">詳細</th>
                </tr>

                @foreach($reqs as $req)
                <tr>
                    <td class="table__item">{{ $req->status_label }}</td>
                    <td class="table__item">{{ $req->user->name}}</td>
                    <td class="table__item">{{ $req->attendance_time}}</td>
                    <td class="table__item">{{ $req->reason }}</td>
                    <td class="table__item">{{ $req->request_time}}</td>
                    <td class="table__item">
                        <a class="detail-link" href="{{route('user.detail', ['id' => $req->attendance->id])}}">
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

