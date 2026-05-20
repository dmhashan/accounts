<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formTitle }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1a1a1a; padding: 24px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 18px; font-weight: bold; }
        .header p { color: #aaaaaa; margin: 6px 0 0; font-size: 13px; }
        .body { padding: 32px; color: #374151; font-size: 15px; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .note { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; font-size: 13px; color: #6b7280; margin-top: 24px; }
        .footer { padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $formTitle }}</h1>
            <p>Form Submission</p>
        </div>
        <div class="body">
            <p>Hi {{ $memberName }},</p>
            <p>Thank you for completing the <strong>{{ $formTitle }}</strong> form. A copy of your completed submission is attached to this email as a PDF for your records.</p>
            <div class="note">
                Please keep this document for your reference. If you have any questions, contact your facility directly.
            </div>
        </div>
        <div class="footer">
            This is an automated message. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
