<!DOCTYPE html>
<html lang="{{ $language ?? 'en' }}">
<head>
<meta charset="UTF-8" />
<title>{{ $template->title }}</title>
<style>
    * { margin: 0; padding: 0; }

    body {
        font-family: {!! $bodyFont ?? 'dejavusans, sans-serif' !!};
        font-size: 10pt;
        color: #1a1a1a;
        line-height: 1.5;
        direction: {{ ($isRtl ?? false) ? 'rtl' : 'ltr' }};
    }

    /* ── Red top bar ──────────────────────────────────────── */
    .top-bar { background: #c8102e; height: 5px; width: 100%; font-size: 0; }

    /* ── Letterhead ───────────────────────────────────────── */
    .lh-table { width: 100%; border-collapse: collapse; background: #4a4a4a; }
    .lh-brand-td {
        padding: 8px 28px;
        vertical-align: middle;
    }
    .lh-logo-wrap { margin-bottom: 0; }
    .lh-logo-img { max-width: 400px; max-height: 144px; object-fit: contain; display: block; }
    .lh-contact-name { font-weight: bold; color: #fff; font-size: 10pt; }
    .lh-org-name {
        font-size: 12pt;
        font-weight: bold;
        color: #fff;
        line-height: 1.2;
    }
    .lh-contact-td {
        width: 360px;
        padding: 8px 28px;
        vertical-align: middle;
        text-align: right;
    }
    .lh-contact-line { font-size: 8pt; color: #ccc; line-height: 1.7; }

    /* ── Rule ─────────────────────────────────────────────── */
    .lh-rule { display: none; }

    /* ── Title band ───────────────────────────────────────── */
    .title-table { width: 100%; border-collapse: collapse; background: #1a1a1a; }
    .title-stripe-td { width: 6px; background: #c8102e; }
    .title-body-td { padding: 13px 30px; vertical-align: middle; }
    .title-h1 {
        font-size: 11.5pt;
        font-weight: bold;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }
    .title-sub { font-size: 8pt; color: #aaa; margin-top: 4px; line-height: 1.4; }

    /* ── Body ─────────────────────────────────────────────── */
    .body-wrap { padding: 20px 36px 32px; }

    /* ── Meta box ─────────────────────────────────────────── */
    .meta-outer {
        border: 1.5px solid #ddd;
        border-top: 3px solid #c8102e;
        background: #f9f9f9;
        margin-bottom: 22px;
    }
    .meta-table { width: 100%; border-collapse: collapse; }
    .meta-td {
        width: 33.33%;
        padding: 12px 16px;
        vertical-align: top;
        border-right: 1px solid #e4e4e4;
    }
    .meta-td:last-child { border-right: none; }
    .meta-label {
        font-size: 6.5pt;
        font-weight: bold;
        color: #aaa;
        text-transform: uppercase;
        letter-spacing: 0.9px;
        margin-bottom: 4px;
    }
    .meta-value { font-size: 10.5pt; font-weight: bold; color: #111; }

    /* ── Fields ───────────────────────────────────────────── */
    .field-block { margin-bottom: 17px; }
    .field-label {
        font-size: 7.5pt;
        font-weight: bold;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .field-value {
        font-size: 10.5pt;
        color: #1a1a1a;
        border-bottom: 1.5px solid #bbb;
        min-height: 26px;
        padding: 3px 2px;
        word-break: break-word;
    }
    .field-value.empty { color: #ccc; font-style: italic; }
    .textarea-value {
        font-size: 10.5pt;
        color: #1a1a1a;
        border: 1px solid #d0d0d0;
        min-height: 54px;
        padding: 7px 10px;
        background: #fafafa;
        word-break: break-word;
    }
    .textarea-value.empty { color: #ccc; font-style: italic; }
    .field-heading {
        font-size: 10.5pt;
        font-weight: bold;
        color: #111;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        border-bottom: 2px solid #111;
        padding-bottom: 5px;
        margin-top: 26px;
        margin-bottom: 13px;
    }
    .field-paragraph { font-size: 9pt; color: #666; font-style: italic; margin-bottom: 11px; line-height: 1.5; }

    /* ── Checkbox / radio ─────────────────────────────────── */
    .cb-table { width: 100%; margin-bottom: 5px; border-collapse: collapse; }
    .cb-cell-box { width: 16px; vertical-align: middle; padding: 0; }
    .cb-cell-label { vertical-align: middle; padding-left: 8px; font-size: 10pt; color: #222; }
    .cb-box {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 1.5px solid #888;
        text-align: center;
        font-size: 9pt;
        font-family: dejavusans, sans-serif;
        color: #fff;
        line-height: 12px;
        border-radius: 2px;
    }
    .cb-box.checked { background: #c8102e; border-color: #c8102e; }

    /* ── Signature ────────────────────────────────────────── */
    .sig-line { border-bottom: 1px solid #555; height: 36px; width: 200px; margin-bottom: 4px; }
    .signature-img { max-width: 220px; max-height: 72px; border-bottom: 1.5px solid #555; display: block; }

    /* ── Footer ───────────────────────────────────────────── */
    .footer { margin-top: 34px; padding-top: 8px; border-top: 1px solid #e0e0e0; }
    .footer-table { width: 100%; border-collapse: collapse; }
    .footer-left-td { font-size: 7.5pt; color: #bbb; text-align: left; vertical-align: top; }
    .footer-right-td { font-size: 7.5pt; color: #bbb; text-align: right; vertical-align: top; }

    /* ── Misc ─────────────────────────────────────────────── */
    .required-note { font-size: 7.5pt; color: #c8102e; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>

{{-- Top red bar --}}
<div class="top-bar">&nbsp;</div>

{{-- Letterhead --}}
<table class="lh-table">
    <tr>
        <td class="lh-brand-td">
            @if(!empty($tenantLogoBase64))
            <div class="lh-logo-wrap">
                <img src="{{ $tenantLogoBase64 }}" alt="{{ $tenantName ?? '' }}" class="lh-logo-img" />
            </div>
            @endif
        </td>
        @if(!empty($tenantName) || !empty($tenantAddress) || !empty($tenantEmail) || !empty($tenantPhone))
        <td class="lh-contact-td">
            @if(!empty($tenantName))<div class="lh-contact-line lh-contact-name">{{ $tenantName }}</div>@endif
            @if(!empty($tenantAddress))<div class="lh-contact-line">{{ $tenantAddress }}</div>@endif
            @if(!empty($tenantEmail))<div class="lh-contact-line">{{ $tenantEmail }}</div>@endif
            @if(!empty($tenantPhone))<div class="lh-contact-line">{{ $tenantPhone }}</div>@endif
        </td>
        @endif
    </tr>
</table>

<hr class="lh-rule" />

{{-- Title band --}}
<table class="title-table">
    <tr>
        <td class="title-stripe-td">&nbsp;</td>
        <td class="title-body-td">
            <div class="title-h1">{{ $template->title }}</div>
            @if($template->description)
            <div class="title-sub">{{ $template->description }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="body-wrap">

    {{-- Member meta --}}
    <div class="meta-outer">
        <table class="meta-table">
            <tr>
                <td class="meta-td">
                    <div class="meta-label">Member Name</div>
                    <div class="meta-value">{{ $memberName }}</div>
                </td>
                <td class="meta-td">
                    <div class="meta-label">Member ID</div>
                    <div class="meta-value">{{ $memberId ?: '—' }}</div>
                </td>
                <td class="meta-td">
                    <div class="meta-label">Date Submitted</div>
                    <div class="meta-value">{{ $submittedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Form fields --}}
    @foreach($resolvedFields ?? $template->fields ?? [] as $field)
        @php
            $type  = $field['type'] ?? 'text';
            $fid   = $field['id']   ?? '';
            $value = $submission->responses[$fid] ?? null;
        @endphp

        @if($type === 'heading')
            <div class="field-heading">{{ $field['label'] }}</div>

        @elseif($type === 'paragraph')
            <div class="field-paragraph">{!! nl2br(e($field['label'])) !!}</div>

        @elseif($type === 'checkbox')
            <div class="field-block">
                <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
                <table class="cb-table"><tr>
                    <td class="cb-cell-box"><span class="cb-box {{ $value ? 'checked' : '' }}">{!! $value ? '&#x2713;' : '' !!}</span></td>
                    <td class="cb-cell-label">{{ $value ? 'Yes' : 'No' }}</td>
                </tr></table>
            </div>

        @elseif($type === 'radio' || $type === 'select')
            <div class="field-block">
                <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
                @if(!empty($field['options']))
                    @foreach($field['options'] as $opt)
                        @php $isSelected = (string)$value === (string)$opt; @endphp
                        <table class="cb-table"><tr>
                            <td class="cb-cell-box"><span class="cb-box {{ $isSelected ? 'checked' : '' }}">{!! $isSelected ? '&#x2713;' : '' !!}</span></td>
                            <td class="cb-cell-label">{{ $opt }}</td>
                        </tr></table>
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
                    <div class="sig-line"></div>
                @endif
            </div>

        @elseif($type === 'textarea')
            <div class="field-block">
                <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
                <div class="textarea-value {{ $value ? '' : 'empty' }}">{{ $value ?? '' }}</div>
            </div>

        @else
            <div class="field-block">
                <div class="field-label">{{ $field['label'] }}@if(!empty($field['required']))<span class="required-note"> *</span>@endif</div>
                <div class="field-value {{ $value ? '' : 'empty' }}">{{ $value ?? '' }}</div>
            </div>
        @endif
    @endforeach

    {{-- Footer --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left-td">
                    @if(!empty($tenantName)){{ $tenantName }} &nbsp;&middot;&nbsp; @endif Submission #{{ $submission->id }}
                </td>
                <td class="footer-right-td">Generated: {{ $submittedAt }}</td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
