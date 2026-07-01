@extends('emails.layout')

@section('title', 'Reset your ' . $tenantName . ' password')
@section('preheader', 'Use this secure link to choose a new password.')
@section('eyebrow')
    @if($recipientName)
        Hello, {{ $recipientName }}
    @else
        Hello
    @endif
@endsection
@section('heading', 'Reset your password')
@section('intro', 'Use the link below to choose a new password for your account.')

@section('content')
    <p>
        A password reset was requested for your {{ $tenantName }} account. This link will expire in 60 minutes.
    </p>

    <p style="text-align:center;margin:26px 0;">
        <a class="button" href="{{ $resetUrl }}">Reset password</a>
    </p>

    <div class="panel">
        <p style="margin-bottom:8px;">If the button does not work, copy and paste this URL into your browser:</p>
        <a href="{{ $resetUrl }}" style="word-break:break-all;">{{ $resetUrl }}</a>
    </div>

    <p class="muted">
        If you did not request this, you can ignore this email.
    </p>
@endsection
