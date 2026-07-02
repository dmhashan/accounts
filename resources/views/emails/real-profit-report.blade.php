@extends('emails.layout')

@php
    $money = fn ($value) => number_format((float) $value, 2);
    $realProfit = (float) ($summary['real_profit'] ?? 0);
@endphp

@section('title', 'Real Profit Report')
@section('preheader', 'Real profit report for ' . $monthLabel . ' is attached as a PDF.')
@section('icon', 'R')
@section('eyebrow', 'Real Profit')
@section('heading', 'Report ready for ' . $monthLabel)
@section('intro', 'The real profit report has been generated and attached for review.')

@section('content')
    <p>Hello,</p>
    <p>The full real profit report is attached to this email as a PDF.</p>

    <div class="panel">
        <div><strong style="color:#111827;">Tenant:</strong> {{ $tenantName }}</div>
        <div><strong style="color:#111827;">Month:</strong> {{ $monthLabel }}</div>
        <div><strong style="color:#111827;">Membership income:</strong> {{ $money($summary['membership_income'] ?? 0) }}</div>
        <div><strong style="color:#111827;">Other payments:</strong> {{ $money($summary['other_payment_income'] ?? 0) }}</div>
        <div><strong style="color:#111827;">Sales profit:</strong> {{ $money($summary['sales_profit'] ?? 0) }}</div>
        <div><strong style="color:#111827;">Expenses:</strong> {{ $money($summary['expenses'] ?? 0) }}</div>
        <div><strong style="color:#111827;">Payment fees:</strong> {{ $money($summary['payment_deductions'] ?? 0) }}</div>
        <div style="margin-top:10px;">
            @if($realProfit >= 0)
                <span class="success">Real profit: +{{ $money($realProfit) }}</span>
            @else
                <span class="badge">Real profit: -{{ $money(abs($realProfit)) }}</span>
            @endif
        </div>
    </div>

    <p class="muted" style="font-size:13px;">
        Formula: membership income + other payments + sales profit - expenses.
    </p>
@endsection
