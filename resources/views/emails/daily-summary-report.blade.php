<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Summary Report</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1a1a1a; padding: 24px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 18px; font-weight: bold; }
        .header p { color: #aaaaaa; margin: 6px 0 0; font-size: 13px; }
        .body { padding: 32px; color: #374151; font-size: 15px; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .meta { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; font-size: 14px; color: #374151; margin: 0 0 20px; }
        .meta strong { color: #111827; }
        .badge { display: inline-block; background: #fee2e2; color: #b91c1c; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: bold; }
        .note { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; font-size: 13px; color: #6b7280; margin-top: 8px; }
        .footer { padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $tenantName }}</h1>
            <p>Daily Summary Report</p>
        </div>
        <div class="body">
            <p>Hello,</p>
            <p>A daily summary report has been prepared and signed off. The full report is attached to this email as a PDF.</p>
            <div class="meta">
                <div><strong>Date:</strong> {{ $dateLabel }}</div>
                <div><strong>Prepared by:</strong> {{ $preparedByName }}</div>
                <div style="margin-top:6px;">
                    @if($changeCount > 0)
                        <span class="badge">{{ $changeCount }} manual {{ $changeCount === 1 ? 'adjustment' : 'adjustments' }}</span>
                    @else
                        <span style="color:#16a34a; font-weight:bold; font-size:13px;">No manual adjustments</span>
                    @endif
                </div>
            </div>
            <div class="note">
                Adjusted values (if any) are highlighted in red in the attached PDF, showing both the original system figure and the corrected value.
            </div>
        </div>
        <div class="footer">
            This is an automated message. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
