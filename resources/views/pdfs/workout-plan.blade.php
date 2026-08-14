<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>{{ $title }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1a1a1a; line-height: 1.4; background: #fff; }

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

    /* Day Card */
    .day-container { margin-bottom: 14px; page-break-inside: avoid; }
    .day-header { background: #262626; color: #fff; padding: 6px 12px; font-size: 9pt; font-weight: bold; border-left: 4px solid #c8102e; }
    
    /* Exercises Table */
    table.exercises { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.exercises th {
        background: #f5f5f5; color: #404040; font-size: 7pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 6px 8px; text-align: left; border: 1px solid #e5e5e5;
    }
    table.exercises td {
        padding: 6px 8px; font-size: 8pt; vertical-align: top; border: 1px solid #e5e5e5; color: #171717;
    }
    table.exercises tr:nth-child(even) td { background: #fafafa; }
    .col-num { width: 24px; text-align: center; font-weight: bold; color: #737373; }
    .col-ex { font-weight: bold; color: #171717; }
    .col-target { font-size: 7.5pt; color: #737373; }
    .col-stat { width: 60px; text-align: center; }
    .col-tempo { width: 55px; text-align: center; }
    .col-rest { width: 50px; text-align: center; }
    .col-notes { font-size: 7.5pt; color: #525252; font-style: italic; }

    /* Extras */
    .extras-section { margin-top: 14px; border: 1px solid #e5e5e5; border-radius: 4px; padding: 10px 14px; background: #fafafa; page-break-inside: avoid; }
    .extras-title { font-size: 8.5pt; font-weight: bold; text-transform: uppercase; color: #171717; border-bottom: 1.5px solid #c8102e; padding-bottom: 3px; margin-bottom: 6px; }
    .extra-item { font-size: 8pt; margin-bottom: 4px; }
    .extra-badge { display: inline-block; padding: 1px 6px; font-size: 6.5pt; font-weight: bold; text-transform: uppercase; border-radius: 3px; background: #e5e5e5; color: #404040; margin-right: 4px; }

    .footer { margin-top: 16px; border-top: 1px solid #e5e5e5; padding-top: 8px; font-size: 7pt; color: #737373; text-align: center; }
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
            <div class="title-sub">Member Training &amp; Workout Schedule</div>
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

    @if(!empty($description))
        <div style="font-size: 8pt; color: #525252; margin-bottom: 12px; line-height: 1.4;">
            {{ $description }}
        </div>
    @endif

    <!-- Workout Days & Exercises -->
    @foreach($days as $day)
        <div class="day-container">
            <div class="day-header">
                {{ $day['title'] }} @if(!empty($day['description'])) &mdash; <span style="font-weight: normal; font-size: 8pt; opacity: 0.85;">{{ $day['description'] }}</span> @endif
            </div>

            <table class="exercises">
                <thead>
                    <tr>
                        <th style="width: 20px; text-align: center;">#</th>
                        <th style="width: 180px;">Exercise</th>
                        <th style="width: 100px;">Target</th>
                        <th style="width: 45px; text-align: center;">Sets</th>
                        <th style="width: 55px; text-align: center;">Reps</th>
                        <th style="width: 55px; text-align: center;">Tempo</th>
                        <th style="width: 55px; text-align: center;">Rest</th>
                        <th>Notes / Form Guidance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($day['exercises'] as $idx => $ex)
                        <tr>
                            <td class="col-num">{{ $idx + 1 }}</td>
                            <td class="col-ex">
                                {{ $ex['name'] }}
                                @if(!empty($ex['variation']))
                                    <div style="font-size: 7pt; font-weight: normal; color: #737373;">({{ $ex['variation'] }})</div>
                                @endif
                            </td>
                            <td class="col-target">{{ $ex['target_muscle'] ?: '-' }}</td>
                            <td class="col-stat">{{ $ex['sets'] }}</td>
                            <td class="col-stat">{{ $ex['reps'] }}</td>
                            <td class="col-tempo">{{ $ex['tempo'] ?: '-' }}</td>
                            <td class="col-rest">{{ $ex['rest'] ? $ex['rest'].'s' : '-' }}</td>
                            <td class="col-notes">{{ $ex['notes'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: #a3a3a3; padding: 10px;">No exercises recorded for this day.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <!-- Extras (Core & Cardio) -->
    @if(!empty($extras) && count($extras) > 0)
        <div class="extras-section">
            <div class="extras-title">Additional Training &amp; Protocols</div>
            @foreach($extras as $extra)
                <div class="extra-item">
                    <span class="extra-badge">{{ $extra['type'] }}</span>
                    <strong>{{ $extra['title'] }}</strong>:
                    <span style="color: #525252;">{{ $extra['description'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="footer">
        Generated on {{ $generatedAt }} &bull; {{ $tenantName }} &bull; Consistent training delivers results!
    </div>
</div>

</body>
</html>
