<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>{{ $title }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1a1a1a; line-height: 1.45; background: #fff; }

    .top-bar { background: #c8102e; height: 5px; width: 100%; font-size: 0; }

    .header-table { width: 100%; border-collapse: collapse; background: #262626; color: #fff; }
    .header-brand-td { padding: 14px 24px; vertical-align: middle; }
    .header-logo { max-width: 180px; max-height: 60px; object-fit: contain; display: block; }
    .header-org-name { font-size: 13pt; font-weight: bold; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
    .header-contact-td { padding: 14px 24px; vertical-align: middle; text-align: right; width: 280px; }
    .header-contact-line { font-size: 7.5pt; color: #d4d4d4; line-height: 1.5; }

    .title-stripe { width: 100%; border-collapse: collapse; background: #171717; }
    .title-stripe-bar { width: 6px; background: #c8102e; }
    .title-stripe-content { padding: 10px 24px; vertical-align: middle; }
    .title-text { font-size: 12pt; font-weight: bold; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
    .title-sub { font-size: 8pt; color: #a3a3a3; margin-top: 2px; }

    .body-wrap { padding: 16px 24px 24px; }

    /* Member & Plan Info Grid */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; border: 1px solid #e5e5e5; border-radius: 4px; }
    .info-td { width: 25%; padding: 8px 12px; vertical-align: top; border-right: 1px solid #f0f0f0; background: #fafafa; }
    .info-td:last-child { border-right: none; }
    .info-label { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; color: #737373; letter-spacing: 0.5px; }
    .info-val { font-size: 9pt; font-weight: bold; color: #171717; margin-top: 2px; }

    .notes-box { background: #fffbeb; border: 1px solid #fde68a; border-left: 3.5px solid #f59e0b; padding: 8px 12px; margin-bottom: 14px; border-radius: 3px; font-size: 8pt; color: #92400e; }

    /* Rich text routine content formatting */
    .routine-container {
        font-size: 9pt;
        color: #171717;
        line-height: 1.5;
    }
    .routine-container h1, .routine-container h2, .routine-container h3, .routine-container h4 {
        color: #171717;
        font-weight: bold;
        margin-top: 14px;
        margin-bottom: 6px;
        page-break-inside: avoid;
    }
    .routine-container h1 { font-size: 11.5pt; border-bottom: 2px solid #c8102e; padding-bottom: 3px; }
    .routine-container h2 { font-size: 10.5pt; border-bottom: 1.5px solid #e5e5e5; padding-bottom: 2px; }
    .routine-container h3 { font-size: 9.5pt; color: #c8102e; }
    .routine-container h4 { font-size: 9pt; }

    .routine-container p, .routine-container div {
        margin-bottom: 4px;
    }

    .routine-container ul, .routine-container ol {
        margin: 6px 0 10px 18px;
    }
    .routine-container li {
        margin-bottom: 3px;
        font-size: 8.5pt;
    }

    .routine-container table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        page-break-inside: avoid;
    }
    .routine-container table th {
        background: #f5f5f5;
        color: #171717;
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        padding: 6px 8px;
        border: 1px solid #e5e5e5;
        text-align: left;
    }
    .routine-container table td {
        padding: 6px 8px;
        font-size: 8pt;
        border: 1px solid #e5e5e5;
        vertical-align: top;
    }
    .routine-container table tr:nth-child(even) td {
        background: #fafafa;
    }

    .routine-container blockquote {
        border-left: 3.5px solid #c8102e;
        padding: 6px 12px;
        background: #f9f9f9;
        margin: 8px 0;
        font-style: italic;
        font-size: 8.5pt;
        color: #404040;
    }

    .routine-container pre, .routine-container code {
        font-family: monospace;
        font-size: 8pt;
        background: #f5f5f5;
        border: 1px solid #e5e5e5;
        padding: 2px 5px;
        border-radius: 3px;
    }

    .footer { margin-top: 20px; border-top: 1px solid #e5e5e5; padding-top: 8px; font-size: 7pt; color: #737373; text-align: center; }
</style>
</head>
<body>

<div class="top-bar"></div>

<table class="header-table">
    <tr>
        <td class="header-brand-td">
            @if(!empty($tenantLogo))
                <img src="{{ $tenantLogo }}" class="header-logo" alt="Logo" />
            @else
                <div class="header-org-name">{{ $tenantName }}</div>
            @endif
        </td>
        <td class="header-contact-td">
            @if(!empty($tenantAddress))
                <div class="header-contact-line">{{ $tenantAddress }}</div>
            @endif
            @if(!empty($tenantPhone))
                <div class="header-contact-line">Tel: {{ $tenantPhone }}</div>
            @endif
            @if(!empty($tenantEmail))
                <div class="header-contact-line">Email: {{ $tenantEmail }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="title-stripe">
    <tr>
        <td class="title-stripe-bar"></td>
        <td class="title-stripe-content">
            <div class="title-text">{{ $title }}</div>
            <div class="title-sub">Member Training &amp; Custom Workout Routine</div>
        </td>
    </tr>
</table>

<div class="body-wrap">
    <!-- Info Overview -->
    <table class="info-table">
        <tr>
            <td class="info-td">
                <div class="info-label">Member Name</div>
                <div class="info-val">{{ $memberName }}</div>
            </td>
            <td class="info-td">
                <div class="info-label">Member ID</div>
                <div class="info-val">{{ $memberId ?: '-' }}</div>
            </td>
            <td class="info-td">
                <div class="info-label">Effective Date</div>
                <div class="info-val">{{ $effectiveDate }}</div>
            </td>
            <td class="info-td">
                <div class="info-label">Trainer / Coach</div>
                <div class="info-val">{{ $trainerName ?: 'Gym Staff' }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($notes))
        <div class="notes-box">
            <strong>Trainer Instructions:</strong> {{ $notes }}
        </div>
    @endif

    <!-- Rich Text Workout Routine -->
    <div class="routine-container">
        {!! $formattedText !!}
    </div>

    <div class="footer">
        Generated on {{ $generatedAt }} &bull; {{ $tenantName }} &bull; Consistent training delivers results!
    </div>
</div>

</body>
</html>
