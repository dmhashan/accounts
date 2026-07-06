@extends('emails.layout')

@section('title', 'Account Balance Adjustment ' . ucfirst($action))
@section('preheader', 'An account balance adjustment has been ' . $action . '.')
@section('eyebrow', 'Hello ' . ($recipientName ?? 'Admin'))
@section('heading', 'Account Balance Adjustment')
@section('intro', 'An account balance adjustment has been ' . $action . '.')

@section('content')
    <div class="panel">
        <p style="margin: 0 0 10px 0;"><strong>Action:</strong> <span class="badge">{{ ucfirst($action) }}</span></p>
        <p style="margin: 0 0 10px 0;"><strong>Account:</strong> {{ $adjustmentDetails['account_name'] }}</p>
        <p style="margin: 0 0 10px 0;"><strong>Type:</strong> {{ $adjustmentDetails['type'] === 'credit' ? 'Credit (Increase Balance)' : 'Debit (Decrease Balance)' }}</p>
        <p style="margin: 0 0 10px 0;"><strong>Amount:</strong> {{ number_format($adjustmentDetails['amount'], 2) }}</p>
        <p style="margin: 0 0 10px 0;"><strong>Date:</strong> {{ $adjustmentDetails['date'] }}</p>
        @if(!empty($adjustmentDetails['operator_name']))
            <p style="margin: 0 0 10px 0;"><strong>Performed By:</strong> {{ $adjustmentDetails['operator_name'] }}</p>
        @endif
    </div>

    <div style="background: #f1f5f9; padding: 16px; border-radius: 6px; margin-top: 16px;">
        <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 14px; font-weight: bold;">Adjustment Reason:</h4>
        <p style="margin: 0; font-style: italic; color: #475569; font-size: 14px; line-height: 1.5;">
            {{ $adjustmentDetails['reason'] }}
        </p>
    </div>
@endsection
