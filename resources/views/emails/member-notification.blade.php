@extends('emails.layout')

@section('title', $notificationTitle)
@section('preheader', $notificationBody)
@section('icon', 'N')
@section('eyebrow')
    @if($recipientName)
        Hello, {{ $recipientName }}
    @else
        Hello
    @endif
@endsection
@section('heading', $notificationTitle)
@section('intro', 'A quick update from ' . (($tenantBranding['name'] ?? null) ?: 'your team') . '.')

@section('content')
    <div class="panel">
        {!! nl2br(e($notificationBody)) !!}
    </div>

    @if(!empty($tenantBranding['profile_url']))
        <p style="text-align:center;margin-top:24px;">
            <a class="button" href="{{ $tenantBranding['profile_url'] }}">Open member portal</a>
        </p>
    @endif
@endsection
