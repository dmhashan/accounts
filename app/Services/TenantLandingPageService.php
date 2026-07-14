<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TenantLandingPageService
{
    public function getCustomPagePath(Tenant $tenant): string
    {
        $fileName = Str::slug($tenant->domain) . '.html';

        return storage_path('app/tenant-pages/' . $fileName);
    }

    public function ensureCustomPageExists(Tenant $tenant): string
    {
        $directory = storage_path('app/tenant-pages');
        $filePath = $this->getCustomPagePath($tenant);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (!File::exists($filePath)) {
            File::put($filePath, $this->buildDefaultCustomHtml($tenant));
        }

        return $filePath;
    }

    private function buildDefaultCustomHtml(Tenant $tenant): string
    {
        $tenantName = e($tenant->name ?: $tenant->domain);
        $tenantAddress = e((string) ($tenant->address ?? ''));
        $tenantPhone = e((string) ($tenant->phone ?? ''));
        $tenantEmail = e((string) ($tenant->email ?? ''));
        $contactLine = trim(implode(' | ', array_filter([$tenantPhone, $tenantEmail])));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{$tenantName}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            max-width: 640px;
            width: 100%;
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        h1 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 32px;
        }
        p {
            margin-bottom: 24px;
            color: #475569;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .button {
            display: inline-block;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: 600;
        }
        .button-primary {
            background: #2563eb;
            color: #ffffff;
        }
        .button-secondary {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome to {$tenantName}</h1>
        <p>This is your tenant landing page.</p>
        <p>{$tenantAddress}</p>
        <p>{$contactLine}</p>
        <div class="actions">
            <a class="button button-primary" href="/login">Login</a>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
