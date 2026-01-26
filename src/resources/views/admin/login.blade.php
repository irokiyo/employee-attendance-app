@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="card">
        <h2 class="title">管理者ログイン</h2>

        <form action="{{route('login')}}" method="POST" class="login-form">
            @csrf
            <div class="form-group">
                <input type="hidden" name="login_type" value="admin">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password">
                @error('password')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn">管理者ログインする</button>
        </form>
    </div>
</div>
@endsection

