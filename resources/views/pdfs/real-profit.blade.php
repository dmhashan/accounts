<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Real Profit Report</title>
<style>
    * { margin: 0; padding: 0; }
    body { font-family: dejavusans, sans-serif; font-size: 8.5pt; color: #1f2937; line-height: 1.42; }
    .top-bar { background: #0f766e; height: 5px; width: 100%; font-size: 0; }
    .header { background: #111827; color: #fff; padding: 16px 28px; }
    .tenant { font-size: 13pt; font-weight: bold; }
    .tenant-meta { margin-top: 4px; color: #cbd5e1; font-size: 8pt; }
    .tenant-logo { max-width: 220px; max-height: 58px; object-fit: contain; display: block; }
    .title { padding: 14px 28px; border-bottom: 1px solid #e5e7eb; }
    .title h1 { font-size: 15pt; color: #111827; }
    .title p { color: #64748b; font-size: 8pt; margin-top: 3px; }
    .body { padding: 16px 28px 24px; }
    .section-title { margin: 18px 0 9px; padding-bottom: 4px; border-bottom: 2px solid #0f766e; font-size: 9pt; font-weight: bold; color: #111827; text-transform: uppercase; letter-spacing: .5px; }
    .section-title:first-child { margin-top: 0; }
    table { width: 100%; border-collapse: collapse; }
    .cards { margin-bottom: 7px; }
    .card-td { width: 20%; padding: 3px; }
    .card { border: 1px solid #e5e7eb; padding: 8px 9px; }
    .label { color: #64748b; font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: .35px; }
    .value { margin-top: 3px; color: #111827; font-size: 10pt; font-weight: bold; }
    .formula { border: 1px solid #d1d5db; background: #f9fafb; padding: 9px 11px; margin-top: 8px; font-size: 8pt; }
    .data th { padding: 6px 7px; border-bottom: 1px solid #cbd5e1; background: #f1f5f9; color: #475569; font-size: 6.5pt; text-transform: uppercase; text-align: right; }
    .data th.l { text-align: left; }
    .data td { padding: 6px 7px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
    .data td.l { text-align: left; }
    .data tr.total td { font-weight: bold; background: #f8fafc; border-top: 1.5px solid #cbd5e1; }
    .muted { color: #64748b; font-size: 7.5pt; }
    .pos { color: #047857; }
    .neg { color: #b91c1c; }
    .warn { color: #b45309; }
    .small { font-size: 7pt; color: #64748b; }
</style>
</head>
<body>
@php
    $summary = $report['summary'] ?? [];
    $money = fn ($value) => number_format((float) $value, 2);
    $num = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.') ?: '0';
    $signed = fn ($value) => ((float) $value >= 0 ? '+' : '-') . $money(abs((float) $value));
    $profitClass = fn ($value) => (float) $value >= 0 ? 'pos' : 'neg';
    $method = fn ($value) => ['cash' => 'Cash', 'bank' => 'Bank', 'card' => 'Card', 'member_wallet' => 'Member Wallet'][$value ?? ''] ?? 'Other';
@endphp

<div class="top-bar"></div>
<div class="header">
    @if($tenantLogo)
        <img src="{{ $tenantLogo }}" class="tenant-logo" alt="Logo" />
    @else
        <div class="tenant">{{ $tenantName ?: 'Real Profit Report' }}</div>
    @endif
    <div class="tenant-meta">
        @if($tenantAddress){{ $tenantAddress }} @endif
        @if($tenantPhone)- {{ $tenantPhone }} @endif
        @if($tenantEmail)- {{ $tenantEmail }} @endif
    </div>
</div>
<div class="title">
    <h1>Real Profit Report</h1>
    <p>{{ $report['month_label'] }} - {{ $report['start_date'] }} to {{ $report['end_date'] }} - Generated {{ $generatedAt }}</p>
</div>

<div class="body">
    <table class="cards">
        <tr>
            <td class="card-td">
                <div class="card">
                    <div class="label">Real Profit</div>
                    <div class="value {{ $profitClass($summary['real_profit'] ?? 0) }}">{{ $signed($summary['real_profit'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card">
                    <div class="label">Membership</div>
                    <div class="value pos">+{{ $money($summary['membership_income'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card">
                    <div class="label">Other Payments</div>
                    <div class="value pos">+{{ $money($summary['other_payment_income'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card">
                    <div class="label">Sales Profit</div>
                    <div class="value {{ $profitClass($summary['sales_profit'] ?? 0) }}">{{ $signed($summary['sales_profit'] ?? 0) }}</div>
                </div>
            </td>
            <td class="card-td">
                <div class="card">
                    <div class="label">Expenses</div>
                    <div class="value neg">-{{ $money($summary['expenses'] ?? 0) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="formula">
        Formula: Membership {{ $money($summary['membership_income'] ?? 0) }}
        + Other Payments {{ $money($summary['other_payment_income'] ?? 0) }}
        + Sales Profit {{ $money($summary['sales_profit'] ?? 0) }}
        - Expenses {{ $money($summary['expenses'] ?? 0) }}
        = <span class="{{ $profitClass($summary['real_profit'] ?? 0) }}">{{ $signed($summary['real_profit'] ?? 0) }}</span>
    </div>

    @if(($summary['estimated_cost_items'] ?? 0) > 0 || ($summary['missing_cost_items'] ?? 0) > 0)
        <div class="formula warn">
            {{ $num($summary['estimated_cost_items'] ?? 0) }} sale item costs are estimated and {{ $num($summary['missing_cost_items'] ?? 0) }} are missing cost data.
        </div>
    @endif

    <div class="section-title">Sales Profit by Product</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Product</th>
                <th>Qty</th>
                <th>Revenue</th>
                <th>Cost</th>
                <th>Profit</th>
                <th>Margin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['sales_by_product'] as $item)
                <tr>
                    <td class="l">{{ $item['product_name'] }}</td>
                    <td>{{ $num($item['quantity']) }}</td>
                    <td>{{ $money($item['revenue']) }}</td>
                    <td>{{ $money($item['cost']) }}</td>
                    <td class="{{ $profitClass($item['profit']) }}">{{ $signed($item['profit']) }}</td>
                    <td>{{ $num($item['margin_percent']) }}%</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="6">No sales found for this month.</td></tr>
            @endforelse
            <tr class="total">
                <td class="l">Total</td>
                <td>{{ $num($summary['sales_quantity'] ?? 0) }}</td>
                <td>{{ $money($summary['sales_revenue'] ?? 0) }}</td>
                <td>{{ $money($summary['sales_cost'] ?? 0) }}</td>
                <td class="{{ $profitClass($summary['sales_profit'] ?? 0) }}">{{ $signed($summary['sales_profit'] ?? 0) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Expense Categories</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Category</th>
                <th>Entries</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['expenses_by_category'] as $item)
                <tr>
                    <td class="l">{{ $item['category'] }}</td>
                    <td>{{ $num($item['count']) }}</td>
                    <td class="neg">-{{ $money($item['amount']) }}</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="3">No expenses found for this month.</td></tr>
            @endforelse
            <tr class="total">
                <td class="l">Total</td>
                <td>{{ $num($summary['expense_count'] ?? 0) }}</td>
                <td class="neg">-{{ $money($summary['expenses'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Membership Payments</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Date</th>
                <th class="l">Member</th>
                <th class="l">Plan</th>
                <th class="l">Method</th>
                <th class="l">Period</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['membership_payments'] as $payment)
                <tr>
                    <td class="l">{{ $payment['payment_date'] }}</td>
                    <td class="l">{{ $payment['member_name'] }}</td>
                    <td class="l">{{ $payment['payment_plan_name'] ?: '-' }}</td>
                    <td class="l">{{ $method($payment['payment_method']) }}</td>
                    <td class="l">{{ $payment['start_date'] ?: '-' }} to {{ $payment['end_date'] ?: '-' }}</td>
                    <td class="pos">+{{ $money($payment['amount']) }}</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="6">No membership payments found for this month.</td></tr>
            @endforelse
            <tr class="total">
                <td class="l" colspan="5">Total</td>
                <td class="pos">+{{ $money($summary['membership_income'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Other Payments</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Date</th>
                <th class="l">Member</th>
                <th class="l">Method</th>
                <th class="l">Reference</th>
                <th class="l">Notes</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['other_payments'] as $payment)
                <tr>
                    <td class="l">{{ $payment['payment_date'] }}</td>
                    <td class="l">{{ $payment['member_name'] }}</td>
                    <td class="l">{{ $method($payment['payment_method']) }}</td>
                    <td class="l">{{ $payment['reference_number'] ?: '-' }}</td>
                    <td class="l">{{ $payment['notes'] ?: '-' }}</td>
                    <td class="pos">+{{ $money($payment['amount']) }}</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="6">No other payments found for this month.</td></tr>
            @endforelse
            <tr class="total">
                <td class="l" colspan="5">Total</td>
                <td class="pos">+{{ $money($summary['other_payment_income'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Sales Item Cost Detail</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l">Sale</th>
                <th class="l">Product</th>
                <th>Qty</th>
                <th>Sale Price</th>
                <th>Actual Price</th>
                <th>Revenue</th>
                <th>Cost</th>
                <th>Profit</th>
                <th class="l">Cost Source</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['sales_items'] as $item)
                <tr>
                    <td class="l">#{{ $item['sale_id'] }}<br><span class="small">{{ $item['sale_date'] }}</span></td>
                    <td class="l">{{ $item['product_name'] }}<br><span class="small">{{ $item['variation_name'] ?: '-' }}</span></td>
                    <td>{{ $num($item['quantity']) }}</td>
                    <td>{{ $money($item['unit_price']) }}</td>
                    <td>{{ $money($item['unit_cost']) }}</td>
                    <td>{{ $money($item['revenue']) }}</td>
                    <td>{{ $money($item['cost']) }}</td>
                    <td class="{{ $profitClass($item['profit']) }}">{{ $signed($item['profit']) }}</td>
                    <td class="l">{{ ucfirst($item['cost_source']) }}</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="9">No sale items found for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
