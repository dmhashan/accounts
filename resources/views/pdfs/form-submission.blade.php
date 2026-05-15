<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $template->title }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 11pt;
        color: #1a1a1a;
        line-height: 1.5;
        padding: 30px 36px;
    }
    .header {
        text-align: center;
        border-bottom: 2px solid #1a1a1a;
        padding-bottom: 10px;
        margin-bottom: 16px;
    }
    .header h1 {
        font-size: 16pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .header p {
        font-size: 9pt;
        color: #555;
        margin-top: 3px;
    }
    .meta-row {
        display: table;
        width: 100%;
        margin-bottom: 16px;
        font-size: 9.5pt;
    }
    .meta-cell {
        display: table-cell;
        width: 50%;
        padding: 4px 0;
    }
    .meta-cell strong { font-weight: bold; }
    .field-block {
        margin-bottom: 14px;
    }
    .field-label {
        font-size: 9.5pt;
        font-weight: bold;
        color: #333;
        margin-bottom: 3px;
    }
    .field-value {
        border-bottom: 1px solid #888;
        min-height: 22px;
        padding: 2px 0 2px 2px;
        font-size: 10.5pt;
        color: #1a1a1a;
        word-break: break-word;
    }
    .field-value.empty {
        color: #aaa;
        font-style: italic;
    }
    .field-heading {
        font-size: 12pt;
        font-weight: bold;
        color: #1a1a1a;
        border-bottom: 1px solid #ccc;
        padding-bottom: 4px;
        margin-top: 18px;
        margin-bottom: 8px;
    }
    .field-paragraph {
        font-size: 9.5pt;
        color: #555;
        margin-bottom: 10px;
        font-style: italic;
    }
    .checkbox-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cb-box {
        display: inline-block;
        width: 13px;
        height: 13px;
        border: 1.5px solid #555;
        margin-right: 4px;
        text-align: center;
        line-height: 11px;
        font-size: 10pt;
    }
    .signature-section {
        margin-top: 30px;
        border-top: 1px dashed #888;
        padding-top: 14px;
    }
    .sig-row {
        display: table;
        width: 100%;
        margin-top: 16px;
    }
    .sig-cell {
        display: table-cell;
        width: 50%;
        padding-right: 20px;
    }
    .sig-line {
        border-bottom: 1px solid #333;
        height: 30px;
        margin-bottom: 4px;
    }
    .sig-caption {
        font-size: 8.5pt;
        color: #666;
    }
    .footer {
        margin-top: 30px;
        padding-top: 8px;
        border-top: 1px solid #ddd;
        font-size: 8pt;
        color: #888;
        text-align: center;
    }
    .required-note {
        font-size: 7.5pt;
        color: #cc0000;
    }
    .signature-img {
        max-width: 240px;
        max-height: 80px;
        border-bottom: 1px solid #333;
        display: block;
    }
    .page-break { page-break-before: always; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $template->title }}</h1>
    @if($template->description)
    <p>{{ $template->description }}</p>
    @endif
</div>

<!-- Member + Submission metadata -->
<div class="meta-row">
    <div class="meta-cell">
        <strong>Member Name:</strong><br>
        <span style="border-bottom:1px solid #888;display:block;min-height:18px;padding-top:2px;">{{ $memberName }}</span>
    </div>
    <div class="meta-cell" style="padding-left:16px;">
        <strong>Member ID:</strong><br>
        <span style="border-bottom:1px solid #888;display:block;min-height:18px;padding-top:2px;">{{ $memberId }}</span>
    </div>
</div>
<div class="meta-row">
    <div class="meta-cell">
        <strong>Date Submitted:</strong><br>
        <span style="border-bottom:1px solid #888;display:block;min-height:18px;padding-top:2px;">{{ $submittedAt }}</span>
    </div>
</div>

<hr style="border:none;border-top:1px solid #ccc;margin:10px 0 18px;" />

<!-- Fields -->
@foreach($template->fields ?? [] as $field)
    @php
        $type  = $field['type'] ?? 'text';
        $fid   = $field['id']   ?? '';
        $value = $submission->responses[$fid] ?? null;
    @endphp

    @if($type === 'heading')
        <div class="field-heading">{{ $field['label'] }}</div>
    @elseif($type === 'paragraph')
        <div class="field-paragraph">{{ $field['label'] }}</div>
    @elseif($type === 'checkbox')
        <div class="field-block">
            <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
            <div class="checkbox-row">
                <span class="cb-box">{{ $value ? '✓' : '' }}</span>
                {{ $value ? 'Yes' : 'No' }}
            </div>
        </div>
    @elseif($type === 'radio' || $type === 'select')
        <div class="field-block">
            <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
            @if(!empty($field['options']))
                @foreach($field['options'] as $opt)
                    <div class="checkbox-row" style="margin-bottom:2px;">
                        <span class="cb-box">{{ $value === $opt ? '●' : '' }}</span>
                        {{ $opt }}
                    </div>
                @endforeach
            @else
                <div class="field-value {{ $value ? '' : 'empty' }}">{{ $value ?? '—' }}</div>
            @endif
        </div>
    @elseif($type === 'signature')
        <div class="field-block">
            <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
            @if(!empty($value) && str_starts_with($value, 'data:image'))
                <img src="{{ $value }}" class="signature-img" alt="Signature" />
            @else
                <div class="sig-line" style="width:220px;"></div>
            @endif
        </div>
    @elseif($type === 'textarea')
        <div class="field-block">
            <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
            <div class="field-value {{ $value ? '' : 'empty' }}" style="min-height:48px;border:1px solid #888;padding:4px;">{{ $value ?? '' }}</div>
        </div>
    @else
        <div class="field-block">
            <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
            <div class="field-value {{ $value ? '' : 'empty' }}">{{ $value ?? '' }}</div>
        </div>
    @endif
@endforeach


<div class="footer">
    This document was generated on {{ $submittedAt }}. Submission ID: #{{ $submission->id }}
</div>

</body>
</html>
