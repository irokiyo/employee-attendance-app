@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}" />
@endsection

@section('header')
@include('partials.admin-header')
@endsection

@section('content')
<div class="attendance-list">
    <div class="page">
        <h1 class="page__title">スタッフ一覧</h1>

        <div class="attendance-table">
            <table class="table">
                <tr class="row">
                    <th class="table__header">名前</th>
                    <th class="table__header">メールアドレス</th>
                    <th class="table__header">月次勤怠</th>
                </tr>

                <tr class="row">
                    @foreach($users as $user)
                    <td class="table__item">{{ $user->name }}</td>
                    <td class="table__item">{{ $user->email }}</td>
                    <td class="table__item">
                        <a class="detail-link" href="{{route('admin.attendance.show',['id' => $user->id])}}">
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

