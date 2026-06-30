<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Employee Pay Sheet</title>
<style>
    * { margin: 0; padding: 0; }
    body { font-family: dejavusans, sans-serif; font-size: 9.5pt; color: #1f2937; line-height: 1.45; }
    .top-bar { background: #2563eb; height: 5px; width: 100%; font-size: 0; }
    .header { background: #111827; color: #fff; padding: 18px 28px; }
    .tenant { font-size: 13pt; font-weight: bold; }
    .tenant-meta { margin-top: 4px; color: #cbd5e1; font-size: 8pt; }
    .title { padding: 16px 28px; border-bottom: 1px solid #e5e7eb; }
    .title h1 { font-size: 15pt; color: #111827; }
    .title p { color: #64748b; font-size: 8.5pt; margin-top: 3px; }
    .body { padding: 18px 28px 26px; }
    .section-title { margin: 18px 0 9px; padding-bottom: 4px; border-bottom: 2px solid #2563eb; font-size: 9pt; font-weight: bold; color: #111827; text-transform: uppercase; letter-spacing: .5px; }
    .section-title:first-child { margin-top: 0; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { width: 50%; padding: 6px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    .label { color: #64748b; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .35px; }
    .value { color: #111827; font-weight: bold; margin-top: 2px; }
    .data th { padding: 7px 8px; border-bottom: 1px solid #cbd5e1; background: #f1f5f9; color: #475569; font-size: 7pt; text-transform: uppercase; text-align: right; }
    .data th.l { text-align: left; }
    .data td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
    .data td.l { text-align: left; }
    .group td { background: #dbeafe; color: #1e3a8a; font-size: 8pt; font-weight: bold; text-align: left; text-transform: uppercase; }
    .total td { font-weight: bold; background: #f8fafc; border-top: 1.5px solid #cbd5e1; }
    .muted { color: #64748b; font-size: 8pt; }
    .pos { color: #047857; }
    .neg { color: #b91c1c; }
    .net-box { margin-top: 18px; padding: 14px 16px; border: 2px solid #2563eb; background: #eff6ff; text-align: right; }
    .net-label { color: #1e40af; font-size: 9pt; font-weight: bold; text-transform: uppercase; }
    .net-value { margin-top: 3px; color: #111827; font-size: 22pt; font-weight: bold; }
</style>
</head>
<body>
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $lineDetails = function (array $line): string {
        $parts = [];

        if (!empty($line['details'])) {
            $parts[] = $line['details'];
        } elseif (!empty($line['dates_label'])) {
            $parts[] = $line['dates_label'];
        }

        if (!empty($line['notes'])) {
            $parts[] = $line['notes'];
        }

        return implode(' - ', array_filter($parts));
    };
@endphp

<div class="top-bar"></div>
<div class="header">
    <div class="tenant">{{ $tenantName ?: 'Employee Pay Sheet' }}</div>
    <div class="tenant-meta">
        @if($tenantAddress){{ $tenantAddress }} @endif
        @if($tenantPhone)- {{ $tenantPhone }} @endif
        @if($tenantEmail)- {{ $tenantEmail }} @endif
    </div>
</div>
<div class="title">
    <h1>Employee Pay Sheet</h1>
    <p>Generated {{ $generatedAt }}</p>
</div>

<div class="body">
    <div class="section-title">Employee Details</div>
    <table class="meta">
        <tr>
            <td>
                <div class="label">Employee</div>
                <div class="value">{{ $detail['employee_name'] }}</div>
            </td>
            <td>
                <div class="label">Employee Code</div>
                <div class="value">{{ $detail['employee_code'] ?: '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Role</div>
                <div class="value">{{ $detail['job_title'] ?: '-' }}</div>
            </td>
            <td>
                <div class="label">Department</div>
                <div class="value">{{ $detail['department'] ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Pay Sheet Payment Period</div>
    <table class="meta">
        <tr>
            <td>
                <div class="label">Period</div>
                <div class="value">{{ $detail['period_start'] }} to {{ $detail['period_end'] }}</div>
            </td>
            <td>
                <div class="label">Daily Payment</div>
                <div class="value">{{ $money($detail['daily_rate']) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Month Day Count</div>
                <div class="value">{{ $detail['month_day_count'] }}</div>
            </td>
            <td>
                <div class="label">Payable Days</div>
                <div class="value">{{ $detail['payable_days'] }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Detail</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Description</th>
                <th class="l">Details</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="group">
                <td colspan="3">Earnings</td>
            </tr>
            @foreach($detail['earning_lines'] as $line)
                <tr>
                    <td class="l">
                        <strong>{{ $line['label'] }}</strong>
                        @if(!empty($line['description']))
                            <div class="muted">{{ $line['description'] }}</div>
                        @endif
                    </td>
                    <td class="l">{{ $lineDetails($line) ?: '-' }}</td>
                    <td>{{ $money($line['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td class="l" colspan="2">Total Earnings</td>
                <td>{{ $money($detail['total_earnings']) }}</td>
            </tr>

            <tr class="group">
                <td colspan="3">Deductions</td>
            </tr>
            @forelse($detail['deduction_lines'] as $line)
                <tr>
                    <td class="l">
                        <strong>{{ $line['label'] }}</strong>
                        @if(!empty($line['description']))
                            <div class="muted">{{ $line['description'] }}</div>
                        @endif
                    </td>
                    <td class="l">{{ $lineDetails($line) ?: '-' }}</td>
                    <td class="neg">{{ $money($line['amount']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="l muted" colspan="3">No deductions recorded.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td class="l" colspan="2">Total Deductions</td>
                <td class="neg">{{ $money($detail['total_deductions']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net-box">
        <div class="net-label">Net Pay</div>
        <div class="net-value">{{ $money($detail['net_pay']) }}</div>
    </div>

    @if(!empty($detail['notes']))
        <div class="section-title">Notes</div>
        <div class="muted">{{ $detail['notes'] }}</div>
    @endif
</div>
</body>
</html>
