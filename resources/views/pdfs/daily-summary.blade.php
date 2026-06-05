<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Daily Summary Report</title>
<style>
    * { margin: 0; padding: 0; }
    body { font-family: dejavusans, sans-serif; font-size: 9.5pt; color: #1a1a1a; line-height: 1.45; }

    .top-bar { background: #c8102e; height: 5px; width: 100%; font-size: 0; }

    .lh-table { width: 100%; border-collapse: collapse; background: #4a4a4a; }
    .lh-brand-td { padding: 10px 28px; vertical-align: middle; }
    .lh-logo-img { max-width: 240px; max-height: 70px; object-fit: contain; display: block; }
    .lh-org-name { font-size: 13pt; font-weight: bold; color: #fff; line-height: 1.2; }
    .lh-contact-td { width: 300px; padding: 10px 28px; vertical-align: middle; text-align: right; }
    .lh-contact-line { font-size: 8pt; color: #ccc; line-height: 1.6; }

    .title-table { width: 100%; border-collapse: collapse; background: #1a1a1a; }
    .title-stripe-td { width: 6px; background: #c8102e; }
    .title-body-td { padding: 12px 30px; vertical-align: middle; }
    .title-h1 { font-size: 12pt; font-weight: bold; color: #fff; text-transform: uppercase; letter-spacing: 1.2px; }
    .title-sub { font-size: 8pt; color: #aaa; margin-top: 3px; }

    .body-wrap { padding: 18px 30px 26px; }

    .section-title {
        font-size: 9.5pt; font-weight: bold; color: #111; text-transform: uppercase;
        letter-spacing: 0.8px; border-bottom: 2px solid #c8102e; padding-bottom: 4px;
        margin: 20px 0 10px;
    }
    .section-title:first-child { margin-top: 0; }

    table.data { width: 100%; border-collapse: collapse; }
    table.data th {
        background: #f1f1f1; color: #555; font-size: 7pt; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 6px 8px; text-align: right; border-bottom: 1.5px solid #ddd;
    }
    table.data th.l { text-align: left; }
    table.data td { padding: 6px 8px; font-size: 9pt; text-align: right; border-bottom: 1px solid #eee; }
    table.data td.l { text-align: left; }
    table.data tr.total-row td { font-weight: bold; background: #fafafa; border-top: 1.5px solid #ccc; border-bottom: none; }
    .pos { color: #15803d; }
    .neg { color: #b91c1c; }
    .edited { color: #c8102e; font-weight: bold; }
    .was { color: #999; font-size: 7pt; text-decoration: line-through; }

    /* Prepared-by / signer block */
    .signer-table { width: 100%; border-collapse: collapse; border: 1.5px solid #ddd; border-top: 3px solid #c8102e; background: #f9f9f9; }
    .signer-td { width: 33.33%; padding: 12px 16px; vertical-align: top; border-right: 1px solid #e4e4e4; text-align: center; }
    .signer-td:last-child { border-right: none; }
    .signer-label { font-size: 6.5pt; font-weight: bold; color: #aaa; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 6px; }
    .signer-name { font-size: 11pt; font-weight: bold; color: #111; }
    .signer-img { max-width: 150px; max-height: 80px; object-fit: contain; }
    .selfie-img { max-width: 90px; max-height: 90px; object-fit: cover; border-radius: 4px; }
    .placeholder { color: #ccc; font-style: italic; font-size: 8pt; }

    /* Headline cards */
    .cards-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .card-td { width: 25%; padding: 4px; }
    .card-inner { border: 1.5px solid #e4e4e4; border-radius: 4px; padding: 9px 11px; }
    .card-label { font-size: 6.5pt; font-weight: bold; color: #999; text-transform: uppercase; letter-spacing: 0.6px; }
    .card-value { font-size: 11pt; font-weight: bold; color: #111; margin-top: 3px; }

    .badge { display: inline-block; background: #fee2e2; color: #b91c1c; border-radius: 999px; padding: 1px 9px; font-size: 7.5pt; font-weight: bold; }
    .legend { font-size: 7.5pt; color: #888; margin-top: 6px; }
</style>
</head>
<body>
@php
    $changeMap = [];
    foreach ($changes as $c) {
        $changeMap[$c['section'].'|'.$c['ref'].'|'.$c['field']] = $c;
    }
    $money = fn ($v) => number_format((float) $v, 2);
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.') ?: '0';
    // Render a possibly-edited cell value.
    $cell = function (bool $edited, $value, $original, bool $isMoney = true) use ($money, $num) {
        $fmt = $isMoney ? $money : $num;
        if ($edited) {
            return '<span class="edited">'.$fmt($value).'</span>'
                .'<br><span class="was">'.$fmt($original).'</span>';
        }
        return $fmt($value);
    };
    $accounts = $final['accounts'] ?? [];
    $stockMovements = $final['stock']['movements'] ?? [];
    $stockTotals = $final['stock']['totals'] ?? [];
    $totals = $final['totals'] ?? [];
    $incomeBreak = $final['income']['breakdown'] ?? [];
    $expenseBreak = $final['expense']['breakdown'] ?? [];
@endphp

<div class="top-bar"></div>

<table class="lh-table">
    <tr>
        <td class="lh-brand-td">
            @if($tenantLogo)
                <img src="{{ $tenantLogo }}" class="lh-logo-img" alt="Logo" />
            @else
                <div class="lh-org-name">{{ $tenantName }}</div>
            @endif
        </td>
        <td class="lh-contact-td">
            @if($tenantAddress)<div class="lh-contact-line">{{ $tenantAddress }}</div>@endif
            @if($tenantPhone)<div class="lh-contact-line">{{ $tenantPhone }}</div>@endif
            @if($tenantEmail)<div class="lh-contact-line">{{ $tenantEmail }}</div>@endif
        </td>
    </tr>
</table>

<table class="title-table">
    <tr>
        <td class="title-stripe-td"></td>
        <td class="title-body-td">
            <div class="title-h1">Daily Summary Report</div>
            <div class="title-sub">{{ $report->report_date->format('l, d M Y') }} &nbsp;·&nbsp; Generated {{ $generatedAt }}</div>
        </td>
    </tr>
</table>

<div class="body-wrap">

    {{-- Headline cards --}}
    <table class="cards-table">
        <tr>
            <td class="card-td">
                <div class="card-inner">
                    <div class="card-label">Opening Balance</div>
                    <div class="card-value">{{ $money($totals['opening_balance'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card-inner">
                    <div class="card-label">Income</div>
                    <div class="card-value pos">+{{ $money($totals['income'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card-inner">
                    <div class="card-label">Expenses</div>
                    <div class="card-value neg">-{{ $money($totals['expense'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card-inner">
                    <div class="card-label">Closing Balance</div>
                    <div class="card-value">{{ $money($totals['closing_balance'] ?? 0) }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if(count($changes) > 0)
        <div class="legend">
            <span class="badge">{{ count($changes) }} manual {{ count($changes) === 1 ? 'adjustment' : 'adjustments' }}</span>
            &nbsp; Adjusted values are shown in <span class="edited">red</span> with the original system value struck through below.
        </div>
    @else
        <div class="legend">No manual adjustments — all values are system-calculated.</div>
    @endif

    {{-- Account balances --}}
    <div class="section-title">Account Balances</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Account</th>
                <th>Opening</th>
                <th>Income</th>
                <th>Expense</th>
                <th>Closing</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $a)
                <tr>
                    <td class="l">{{ $a['name'] }}</td>
                    <td>{!! $cell(!empty($a['edited']['opening_balance']), $a['opening_balance'], $changeMap['Account|'.$a['name'].'|Opening Balance']['original'] ?? null) !!}</td>
                    <td>{!! $cell(!empty($a['edited']['income']), $a['income'], $changeMap['Account|'.$a['name'].'|Income']['original'] ?? null) !!}</td>
                    <td>{!! $cell(!empty($a['edited']['expense']), $a['expense'], $changeMap['Account|'.$a['name'].'|Expense']['original'] ?? null) !!}</td>
                    <td>{{ $money($a['closing_balance']) }}</td>
                </tr>
            @empty
                <tr><td class="l" colspan="5" style="color:#999;">No accounts.</td></tr>
            @endforelse
            <tr class="total-row">
                <td class="l">Total</td>
                <td>{{ $money($totals['opening_balance'] ?? 0) }}</td>
                <td>{{ $money($totals['income'] ?? 0) }}</td>
                <td>{{ $money($totals['expense'] ?? 0) }}</td>
                <td>{{ $money($totals['closing_balance'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Income & expense breakdown --}}
    @if(count($incomeBreak) > 0 || count($expenseBreak) > 0)
        <div class="section-title">Income &amp; Expense Breakdown</div>
        <table class="data">
            <thead>
                <tr>
                    <th class="l">Income Source</th>
                    <th>Amount</th>
                    <th class="l" style="border-left:1px solid #eee; padding-left:14px;">Expense Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $rows = max(count($incomeBreak), count($expenseBreak)); @endphp
                @for($i = 0; $i < $rows; $i++)
                    <tr>
                        <td class="l">{{ $incomeBreak[$i]['label'] ?? '' }}</td>
                        <td class="pos">{{ isset($incomeBreak[$i]) ? '+'.$money($incomeBreak[$i]['amount']) : '' }}</td>
                        <td class="l" style="border-left:1px solid #eee; padding-left:14px;">{{ $expenseBreak[$i]['label'] ?? '' }}</td>
                        <td class="neg">{{ isset($expenseBreak[$i]) ? '-'.$money($expenseBreak[$i]['amount']) : '' }}</td>
                    </tr>
                @endfor
                <tr class="total-row">
                    <td class="l">Total Income</td>
                    <td class="pos">+{{ $money($final['income']['total'] ?? 0) }}</td>
                    <td class="l" style="border-left:1px solid #eee; padding-left:14px;">Total Expense</td>
                    <td class="neg">-{{ $money($final['expense']['total'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Stock movement --}}
    <div class="section-title">Stock Movement</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Product</th>
                <th>Opening</th>
                <th>Received</th>
                <th>Sold</th>
                <th>Closing</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockMovements as $s)
                <tr>
                    <td class="l">{{ $s['product_name'] }}</td>
                    <td>{!! $cell(!empty($s['edited']['opening']), $s['opening'], $changeMap['Stock|'.$s['product_name'].'|Opening Units']['original'] ?? null, false) !!}</td>
                    <td>{!! $cell(!empty($s['edited']['received']), $s['received'], $changeMap['Stock|'.$s['product_name'].'|Received']['original'] ?? null, false) !!}</td>
                    <td>{!! $cell(!empty($s['edited']['sold']), $s['sold'], $changeMap['Stock|'.$s['product_name'].'|Sold']['original'] ?? null, false) !!}</td>
                    <td>{{ $num($s['closing']) }}</td>
                    <td>{{ $money($s['revenue']) }}</td>
                </tr>
            @empty
                <tr><td class="l" colspan="6" style="color:#999;">No stock movement.</td></tr>
            @endforelse
            <tr class="total-row">
                <td class="l">Total</td>
                <td>{{ $num($stockTotals['opening'] ?? 0) }}</td>
                <td>{{ $num($stockTotals['received'] ?? 0) }}</td>
                <td>{{ $num($stockTotals['sold'] ?? 0) }}</td>
                <td>{{ $num($stockTotals['closing'] ?? 0) }}</td>
                <td>{{ $money($stockTotals['revenue'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Prepared by --}}
    <div class="section-title">Prepared By &amp; Responsibility</div>
    <table class="signer-table">
        <tr>
            <td class="signer-td">
                <div class="signer-label">Prepared By</div>
                <div class="signer-name">{{ $report->prepared_by_name }}</div>
            </td>
            <td class="signer-td">
                <div class="signer-label">Signature</div>
                @if($signatureImg)
                    <img src="{{ $signatureImg }}" class="signer-img" alt="Signature" />
                @else
                    <div class="placeholder">No signature</div>
                @endif
            </td>
            <td class="signer-td">
                <div class="signer-label">Verification Photo</div>
                @if($selfieImg)
                    <img src="{{ $selfieImg }}" class="selfie-img" alt="Selfie" />
                @else
                    <div class="placeholder">No photo</div>
                @endif
            </td>
        </tr>
    </table>

</div>
</body>
</html>
