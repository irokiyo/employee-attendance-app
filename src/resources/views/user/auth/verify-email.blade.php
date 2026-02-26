@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/mail.css') }}" />
@endsection

@section('content')
<div class="verify-card">
    <p class="verify-text">
        登録していただいたメールアドレスに確認メールを送信しました。<br>
        メール認証を完了してください。
    </p>

    @if (session('status') === 'verification-link-sent')
    <p class="verify-status">確認メールを再送しました。</p>
    @endif
    @env(['local','testing'])
    <a href="http://localhost:8025" target="_blank" class="verify-button">
        認証はこちらから
    </a>
    @endenv

    <form method="POST" action="{{ route('verification.send') }}" class="verify-form">
        @csrf
        <button type="submit" class="verify-link">
            認証メールを再送する
        </button>
    </form>
</div>
@endsection

