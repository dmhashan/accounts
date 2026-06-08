@php
    $brand = $tenantBranding ?? [];
    $brandName = $brand['name'] ?? 'Your Organisation';
    $brandInitial = $brand['initial'] ?? strtoupper(substr($brandName, 0, 1));
    $logoUrl = $brand['logo_url'] ?? null;
    $address = $brand['address'] ?? null;
    $email = $brand['email'] ?? null;
    $phone = $brand['phone'] ?? null;
    $profileUrl = $brand['profile_url'] ?? null;
    $displayRecipientName = $recipientName ?? $memberName ?? null;
    $avatarUrl = $recipientAvatarUrl ?? null;
    $avatarInitials = $recipientInitials ?? strtoupper(substr((string) ($displayRecipientName ?? $brandName), 0, 1));
    $showAvatar = !empty($displayRecipientName) || !empty($avatarUrl);
    $emailTitle = trim($__env->yieldContent('title')) ?: $brandName;
    $preheader = trim($__env->yieldContent('preheader'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $emailTitle }}</title>
    <style>
        body { margin: 0; padding: 0; background: #f3f4f6; color: #374151; font-family: Arial, Helvetica, sans-serif; }
        table { border-collapse: collapse; }
        img { border: 0; display: block; line-height: 100%; outline: none; text-decoration: none; }
        a { color: #dc2626; text-decoration: none; }
        .page { width: 100%; background: #f3f4f6; padding: 32px 12px; }
        .shell { width: 100%; max-width: 620px; margin: 0 auto; }
        .card { background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14); }
        .masthead { background: #111827; background-image: linear-gradient(135deg, #111827 0%, #1f2937 55%, #7f1d1d 100%); padding: 36px 48px; }
        .brand-mark { width: 58px; height: 58px; border-radius: 8px; background: #ef4444; color: #ffffff; font-size: 25px; font-weight: 800; text-align: center; line-height: 58px; }
        .brand-name { color: #ffffff; font-size: 25px; font-weight: 800; padding-left: 16px; letter-spacing: 0; line-height: 1.15; }
        .hero-wrap { padding: 0 48px; }
        .hero { background: #ffffff; border: 0; border-radius: 0; padding: 34px 0 18px; text-align: left; box-shadow: none; border-bottom: 1px solid #f1f5f9; }
        .avatar-cell { width: 76px; vertical-align: middle; }
        .avatar { width: 56px; height: 56px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 18px; font-weight: 800; line-height: 56px; text-align: center; box-shadow: 0 8px 18px rgba(185, 28, 28, 0.12); }
        .avatar-img { width: 56px; height: 56px; border-radius: 999px; object-fit: cover; }
        .eyebrow { color: #dc2626; font-size: 12px; font-weight: 800; margin: 0 0 7px; text-transform: uppercase; letter-spacing: .08em; }
        .heading { color: #111827; font-size: 28px; line-height: 1.18; font-weight: 800; margin: 0; letter-spacing: 0; }
        .intro { color: #6b7280; font-size: 14px; line-height: 1.6; margin: 12px 0 0; }
        .body { padding: 24px 48px 42px; color: #4b5563; font-size: 15px; line-height: 1.7; }
        .body p { margin: 0 0 16px; }
        .panel { background: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #ef4444; border-radius: 8px; padding: 18px 20px; margin: 16px 0 22px; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72); }
        .badge { display: inline-block; background: #fee2e2; color: #991b1b; border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 800; }
        .success { color: #15803d; font-size: 13px; font-weight: 800; }
        .button { display: inline-block; background: #dc2626; background-image: linear-gradient(96deg, #ef4444, #b91c1c); border-radius: 8px; color: #ffffff !important; font-weight: 800; padding: 13px 28px; box-shadow: 0 10px 20px rgba(220, 38, 38, 0.22); }
        .divider { height: 1px; line-height: 1px; background: #e5e7eb; }
        .footer { background: #111827; color: #d1d5db; padding: 30px 34px; text-align: center; }
        .footer-name { color: #ffffff; font-size: 15px; font-weight: 800; margin: 0 0 10px; }
        .footer-text { color: #d1d5db; font-size: 12px; line-height: 1.65; margin: 0; }
        .muted { color: #8a8f98; }
        @media only screen and (max-width: 620px) {
            .page { padding: 16px 8px; }
            .masthead { padding: 28px 24px; }
            .brand-mark { width: 52px; height: 52px; font-size: 23px; line-height: 52px; }
            .brand-name { font-size: 21px; padding-left: 13px; }
            .hero-wrap { padding: 0 24px; }
            .hero { padding: 26px 0 18px; }
            .avatar-cell { display: block; width: 100%; padding-bottom: 16px; }
            .hero-content-cell { display: block; width: 100%; }
            .body { padding: 22px 24px 32px; }
            .heading { font-size: 25px; }
            .footer { padding-left: 22px; padding-right: 22px; }
        }
    </style>
</head>
<body>
    @if($preheader !== '')
        <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
            {{ $preheader }}
        </div>
    @endif

    <table role="presentation" class="page" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" class="shell" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="card">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="masthead">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td valign="middle">
                                                                @if($logoUrl)
                                                                    <img src="{{ $logoUrl }}" width="58" height="58" alt="{{ $brandName }}" style="width:58px;height:58px;border-radius:8px;object-fit:contain;">
                                                                @else
                                                                    <div class="brand-mark">{{ $brandInitial }}</div>
                                                                @endif
                                                            </td>
                                                            <td class="brand-name" valign="middle">{{ $brandName }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="hero-wrap">
                                        <table role="presentation" class="hero" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                @if($showAvatar)
                                                    <td class="avatar-cell" valign="middle">
                                                        @if($avatarUrl)
                                                            <img src="{{ $avatarUrl }}" width="58" height="58" alt="{{ $displayRecipientName ?? 'Member' }}" class="avatar-img" style="width:58px;height:58px;border-radius:999px;object-fit:cover;">
                                                        @else
                                                            <div class="avatar">{{ $avatarInitials }}</div>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="hero-content-cell">
                                                    <p class="eyebrow">@yield('eyebrow', 'Hello')</p>
                                                    <h1 class="heading">@yield('heading')</h1>
                                                    @hasSection('intro')
                                                        <p class="intro">@yield('intro')</p>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="body">
                                        @yield('content')
                                    </td>
                                </tr>
                                <tr>
                                    <td class="divider"></td>
                                </tr>
                                <tr>
                                    <td class="footer">
                                        <p class="footer-name">{{ $brandName }}</p>
                                        @if($address || $email || $phone)
                                            <p class="footer-text">
                                                @if($address){{ $address }}@endif
                                                @if($address && ($email || $phone))<br>@endif
                                                @if($email)<a href="mailto:{{ $email }}" style="color:#ffffff;text-decoration:underline;">{{ $email }}</a>@endif
                                                @if($email && $phone)<span class="muted"> &nbsp;|&nbsp; </span>@endif
                                                @if($phone)<a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" style="color:#ffffff;text-decoration:underline;">{{ $phone }}</a>@endif
                                            </p>
                                        @endif
                                        <p class="footer-text" style="margin-top:14px;">This is an automated message from {{ $brandName }}.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
