@extends('emails.layout')

@section('title', $formTitle)
@section('preheader', 'A copy of your completed ' . $formTitle . ' form is attached.')
@section('icon', 'F')
@section('eyebrow', 'Form Submission')
@section('heading', 'Thanks, ' . $memberName)
@section('intro', 'Your completed form has been saved and attached for your records.')

@section('content')
    <p>Hi {{ $memberName }},</p>
    <p>Thank you for completing the <strong>{{ $formTitle }}</strong> form. A copy of your completed submission is attached to this email as a PDF.</p>

    <div class="panel">
        Please keep this document for your reference. If you have any questions, contact {{ $tenantBranding['name'] ?? 'your facility' }} directly.
    </div>
@endsection
