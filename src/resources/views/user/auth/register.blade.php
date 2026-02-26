@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="card">
        <h2 class="title">会員登録</h2>

        <form action="{{route('register')}}" method="POST" class="register-form" novalidate>
            @csrf
            <div class="form-group">
                <label for="name">ユーザー名</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}">
                @error('name')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
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

            <div class="form-group">
                <label for="password_confirm">確認用パスワード</label>
                <input type="password" id="password_confirm" name="password_confirmation">
                @error('password_confirmation')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="fortify__btn">登録する</button>
        </form>

        <a href="{{route('login')}}" class="link">ログインはこちら</a>
    </div>
</div>
@endsection

