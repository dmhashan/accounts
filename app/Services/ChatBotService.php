<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\MemberPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatBotService
{
    /**
     * Ask the Gemini API a question enriched with gym historical context.
     */
    public function ask(string $question): array
    {
        $apiKey = config('services.gemini.key');
        $stats = $this->getHistoricalDataContext();

        if (empty($apiKey)) {
            return [
                'answer' => $this->localFallbackResponse($question, $stats),
                'gemini_connected' => false,
            ];
        }

        $systemPrompt = $this->buildSystemPrompt($stats);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent', [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => "User Question: {$question}"],
                        ],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response.';

                return [
                    'answer' => $answer,
                    'gemini_connected' => true,
                ];
            }

            Log::error('Gemini API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'answer' => "The Gemini API call failed (HTTP {$response->status()}). Falling back to local analysis:\n\n" . $this->localFallbackResponse($question, $stats),
                'gemini_connected' => false,
            ];

        } catch (\Throwable $e) {
            Log::error('Gemini Service Error: ' . $e->getMessage(), ['exception' => $e]);

            return [
                'answer' => "An error occurred connecting to Gemini. Falling back to local analysis:\n\n" . $this->localFallbackResponse($question, $stats),
                'gemini_connected' => false,
            ];
        }
    }

    /**
     * Gather historical statistics from the database.
     */
    public function getHistoricalDataContext(): array
    {
        $now = Carbon::now();
        $sixMonthsAgo = $now->copy()->subMonths(6)->startOfMonth();

        $driver = DB::getDriverName();
        $paymentDateExpr = $driver === 'mysql'
            ? "DATE_FORMAT(payment_date, '%Y-%m')"
            : "strftime('%Y-%m', payment_date)";

        $expenseDateExpr = $driver === 'mysql'
            ? "DATE_FORMAT(expense_date, '%Y-%m')"
            : "strftime('%Y-%m', expense_date)";

        // 1. Monthly Financial History
        $monthlyPayments = MemberPayment::query()
            ->select(
                DB::raw("{$paymentDateExpr} as month_key"),
                DB::raw('SUM(amount) as total_payments'),
            )
            ->where('is_paid', true)
            ->whereBetween('payment_date', [$sixMonthsAgo->toDateString(), $now->toDateString()])
            ->groupBy('month_key')
            ->orderBy('month_key', 'asc')
            ->get();

        $monthlyExpenses = Expense::query()
            ->select(
                DB::raw("{$expenseDateExpr} as month_key"),
                DB::raw('SUM(amount) as total_expenses'),
            )
            ->whereBetween('expense_date', [$sixMonthsAgo->toDateString(), $now->toDateString()])
            ->groupBy('month_key')
            ->orderBy('month_key', 'asc')
            ->get();

        // Merge monthly financial data
        $financials = [];
        $tempMap = [];

        foreach ($monthlyPayments as $p) {
            $key = $p->month_key;

            if (!isset($tempMap[$key])) {
                $tempMap[$key] = ['month' => $key, 'payments' => 0.0, 'expenses' => 0.0];
            }
            $tempMap[$key]['payments'] = (float) $p->total_payments;
        }

        foreach ($monthlyExpenses as $e) {
            $key = $e->month_key;

            if (!isset($tempMap[$key])) {
                $tempMap[$key] = ['month' => $key, 'payments' => 0.0, 'expenses' => 0.0];
            }
            $tempMap[$key]['expenses'] = (float) $e->total_expenses;
        }

        ksort($tempMap);
        $financials = array_values($tempMap);

        // 2. Expected Monthly Recurring Revenue (MRR) based on active plans
        $activeMembers = Member::where('is_active', true)
            ->whereNotNull('payment_plan_id')
            ->with('paymentPlan')
            ->get();

        $estimatedMRR = 0.0;

        foreach ($activeMembers as $member) {
            $plan = $member->paymentPlan;

            if ($plan) {
                $price = (float) $plan->price;
                $durationVal = max(1, (int) $plan->duration_value);
                $durationUnit = $plan->duration_unit;

                $monthlyValue = match ($durationUnit) {
                    'year' => $price / ($durationVal * 12),
                    'month' => $price / $durationVal,
                    'week' => $price * 4.33 / $durationVal,
                    default => $price * 30 / $durationVal, // 'day'
                };
                $estimatedMRR += $monthlyValue;
            }
        }

        // 3. Top Members by Attendance
        $topAttendance = MemberAttendance::query()
            ->select('member_id', DB::raw('COUNT(*) as checkins'))
            ->groupBy('member_id')
            ->orderByDesc('checkins')
            ->limit(5)
            ->with('member:id,name,email,joined_date')
            ->get()
            ->map(fn ($att) => [
                'name' => $att->member ? $att->member->name : 'Unknown Member',
                'email' => $att->member ? $att->member->email : 'N/A',
                'joined_date' => $att->member && $att->member->joined_date ? $att->member->joined_date->toDateString() : 'N/A',
                'checkins' => (int) $att->checkins,
            ])
            ->toArray();

        // 4. Top Members by Payments (Financial Contribution)
        $topPaying = MemberPayment::query()
            ->select('member_id', DB::raw('SUM(amount) as total_paid'))
            ->where('is_paid', true)
            ->groupBy('member_id')
            ->orderByDesc('total_paid')
            ->limit(5)
            ->with('member:id,name,email,joined_date')
            ->get()
            ->map(fn ($pay) => [
                'name' => $pay->member ? $pay->member->name : 'Unknown Member',
                'email' => $pay->member ? $pay->member->email : 'N/A',
                'joined_date' => $pay->member && $pay->member->joined_date ? $pay->member->joined_date->toDateString() : 'N/A',
                'total_paid' => (float) $pay->total_paid,
            ])
            ->toArray();

        // 5. General Counts
        $totalActive = Member::where('is_active', true)->count();
        $totalInactive = Member::where('is_active', false)->count();

        // Most Popular Plans
        $popularPlans = Member::query()
            ->select('payment_plan_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('payment_plan_id')
            ->groupBy('payment_plan_id')
            ->orderByDesc('count')
            ->limit(3)
            ->with('paymentPlan')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->paymentPlan ? $item->paymentPlan->name : 'Unknown Plan',
                'count' => $item->count,
            ])
            ->toArray();

        return [
            'financials' => $financials,
            'estimated_mrr' => round($estimatedMRR, 2),
            'top_attendance' => $topAttendance,
            'top_paying' => $topPaying,
            'counts' => [
                'active_members' => $totalActive,
                'inactive_members' => $totalInactive,
                'popular_plans' => $popularPlans,
            ],
        ];
    }

    /**
     * Construct the prompt detailing historical database statistics.
     */
    private function buildSystemPrompt(array $stats): string
    {
        $financialTable = "| Month | Total Payments | Total Expenses | Net Income |\n| --- | --- | --- | --- |\n";

        foreach ($stats['financials'] as $f) {
            $net = $f['payments'] - $f['expenses'];
            $financialTable .= "| {$f['month']} | LKR " . number_format($f['payments'], 2) . ' | LKR ' . number_format($f['expenses'], 2) . ' | LKR ' . number_format($net, 2) . " |\n";
        }

        $topAttendanceList = '';

        foreach ($stats['top_attendance'] as $i => $m) {
            $rank = $i + 1;
            $topAttendanceList .= "{$rank}. {$m['name']} ({$m['email']}) - Joined: {$m['joined_date']} - {$m['checkins']} check-ins\n";
        }

        $topPayingList = '';

        foreach ($stats['top_paying'] as $i => $m) {
            $rank = $i + 1;
            $topPayingList .= "{$rank}. {$m['name']} ({$m['email']}) - Joined: {$m['joined_date']} - LKR " . number_format($m['total_paid'], 2) . " paid\n";
        }

        $popularPlansList = '';

        foreach ($stats['counts']['popular_plans'] as $p) {
            $popularPlansList .= "- {$p['name']}: {$p['count']} members\n";
        }

        return <<<PROMPT
You are AI Assistant, an advanced AI chatbot integrated into the CXFit gym management system. 
You answer queries from gym administrators using real historical data and metrics. 

Here is the current, up-to-date data retrieved from the database:

### GYM FINANCIAL PERFORMANCE (LAST 6 MONTHS)
{$financialTable}

### EXPECTED MONTHLY REVENUE (MRR)
- Estimated Monthly Recurring Revenue: LKR {$stats['estimated_mrr']}
(Calculated from active member subscription plan rates normalized to a monthly interval).

### TOP MEMBERS BY ATTENDANCE (LOYALTY / CHECK-INS)
{$topAttendanceList}

### TOP MEMBERS BY TOTAL FINANCIAL CONTRIBUTION (PAYMENTS)
{$topPayingList}

### GENERAL GYM STATISTICS
- Active Members: {$stats['counts']['active_members']}
- Inactive Members: {$stats['counts']['inactive_members']}
- Popular Payment Plans:
{$popularPlansList}

### INSTRUCTIONS:
- You must consider this data when answering user questions.
- Maintain a professional, friendly, and helpful tone.
- When asked "what is predicted income for next month?", explain that next month's predicted income is estimated around LKR {$stats['estimated_mrr']} based on active subscriptions, and outline how the historical average monthly payment (which is LKR __calculate_from_table__) compares or influences it.
- When asked "who is the best member and reason it?", you can suggest a candidate based on check-ins (e.g. the top attendee) or total payment (e.g. the highest-paying member) or a combination of both. Provide a detailed reason referencing their actual check-ins, payments, and join date.
- Format numbers nicely with LKR currency prefix.
- If data is empty or zero, state that we need more history.
PROMPT;
    }

    /**
     * Local fallback response processor to handle core questions if Gemini API key is missing.
     */
    private function localFallbackResponse(string $question, array $stats): string
    {
        $q = strtolower($question);

        if (str_contains($q, 'predicted') || str_contains($q, 'next month') || str_contains($q, 'forecast')) {
            $totalPayments = 0.0;
            $count = count($stats['financials']);

            foreach ($stats['financials'] as $f) {
                $totalPayments += $f['payments'];
            }
            $avgPayments = $count > 0 ? $totalPayments / $count : 0.0;
            $mrr = $stats['estimated_mrr'];

            return "**Predicted Income Forecast for Next Month:**\n\n" .
                '- **Estimated Monthly Recurring Revenue (MRR):** LKR ' . number_format($mrr, 2) . " (based on active subscription renewals).\n" .
                '- **Historical Monthly Average:** LKR ' . number_format($avgPayments, 2) . ' (over the last ' . $count . " months).\n\n" .
                "**Prediction:** We predict next month's income will be approximately **LKR " . number_format($mrr > 0 ? $mrr : $avgPayments, 2) . '**. ' .
                'This is calculated from the active memberships currently in the system, reflecting stable recurring contract revenue. If new member signups follow historical trends, actual income may exceed this baseline.';
        }

        if (str_contains($q, 'best member') || str_contains($q, 'top member') || str_contains($q, 'best client')) {
            $topAtt = $stats['top_attendance'][0] ?? null;
            $topPay = $stats['top_paying'][0] ?? null;

            if (!$topAtt && !$topPay) {
                return "We don't have enough member attendance or payment history in the system to determine the best member yet.";
            }

            $response = "**Best Member Evaluation (Based on Database Records):**\n\n";

            if ($topAtt && $topPay && $topAtt['name'] === $topPay['name']) {
                $response .= '🏆 **' . $topAtt['name'] . "** is clearly the best member of CXFit!\n\n" .
                    "**Reasons:**\n" .
                    '- **Attendance leader:** Checked in **' . $topAtt['checkins'] . " times**, showing outstanding consistency and dedication.\n" .
                    '- **Financial contribution:** Paid a total of **LKR ' . number_format($topPay['total_paid'], 2) . "** to the gym.\n" .
                    '- **Loyalty:** Joined on ' . $topAtt['joined_date'] . '.';
            } else {
                $response .= "There are two standout members depending on the metric:\n\n";

                if ($topAtt) {
                    $response .= '⭐ **For Attendance & Consistency: ' . $topAtt['name'] . "**\n" .
                        '- **Reason:** Has checked in **' . $topAtt['checkins'] . ' times** since joining on ' . $topAtt['joined_date'] . ", indicating high loyalty and active engagement.\n\n";
                }

                if ($topPay) {
                    $response .= '💼 **For Financial Contribution: ' . $topPay['name'] . "**\n" .
                        '- **Reason:** Has contributed the highest financial value with a total of **LKR ' . number_format($topPay['total_paid'], 2) . '** paid since joining on ' . $topPay['joined_date'] . ".\n\n";
                }
                $response .= 'Overall, ' . ($topAtt ? '**' . $topAtt['name'] . '** (Attendance Leader)' : '**' . $topPay['name'] . '** (Revenue Leader)') . ' is highly valued for supporting the community.';
            }

            return $response;
        }

        return "Hello! I am the AI Assistant. I can help you analyze financial summaries, member attendance, and subscription plans. \n\n" .
            "For example, you can ask me:\n" .
            "- *What is the predicted income for next month?*\n" .
            "- *Who is the best member and why?*\n" .
            '- *What is the count of active members?*';
    }
}
