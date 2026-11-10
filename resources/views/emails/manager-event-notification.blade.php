<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $eventLabel }}</title>
</head>
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#172033">
    <main style="max-width:620px;margin:24px auto;padding:24px;background:#fff;border-radius:12px">
        <p style="margin:0 0 8px;color:#64748b;font-size:13px">{{ $tenantName }}</p>
        <h1 style="margin:0 0 16px;font-size:20px">{{ $eventLabel }}</h1>
        <p style="margin:0 0 18px;font-size:15px;line-height:1.5">{{ $summary }}</p>
        @if ($details !== [])
            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px">
                @foreach ($details as $label => $value)
                    <tr>
                        <th style="padding:8px 12px 8px 0;text-align:left;color:#64748b;font-weight:600;border-top:1px solid #e2e8f0">{{ $label }}</th>
                        <td style="padding:8px 0;text-align:right;border-top:1px solid #e2e8f0">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        @if ($actorName)
            <p style="margin:18px 0 0;color:#64748b;font-size:12px">Changed by {{ $actorName }}</p>
        @endif
    </main>
</body>
</html>
