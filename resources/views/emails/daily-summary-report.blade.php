@extends('emails.layout')

@section('title', 'Daily Summary Report')
@section('preheader', 'Daily summary report for ' . $dateLabel . ' is attached as a PDF.')
@section('icon', 'R')
@section('eyebrow', 'Daily Summary')
@section('heading', 'Report ready for ' . $dateLabel)
@section('intro', 'The signed daily summary has been prepared and attached for review.')

@section('content')
    <p>Hello,</p>
    <p>The full daily summary report is attached to this email as a PDF.</p>

    <div class="panel">
        <div><strong style="color:#111827;">Tenant:</strong> {{ $tenantName }}</div>
        <div><strong style="color:#111827;">Date:</strong> {{ $dateLabel }}</div>
        <div><strong style="color:#111827;">Prepared by:</strong> {{ $preparedByName }}</div>
        <div style="margin-top:10px;">
            @if($changeCount > 0)
                <span class="badge">{{ $changeCount }} manual {{ $changeCount === 1 ? 'adjustment' : 'adjustments' }}</span>
            @else
                <span class="success">No manual adjustments</span>
            @endif
        </div>
    </div>

    <p class="muted" style="font-size:13px;">
        Adjusted values, if any, are highlighted in red in the attached PDF with both the original system figure and the corrected value.
    </p>
@endsection
