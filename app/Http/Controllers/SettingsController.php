<?php

namespace App\Http\Controllers;

use App\Services\TenantLandingPageService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        return view('settings.index', [
            'tenant' => $tenant,
        ]);
    }

    public function updateLandingPage(Request $request, TenantLandingPageService $tenantLandingPageService)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'use_custom_landing_page' => ['nullable', 'boolean'],
        ]);

        $tenant->update([
            'use_custom_landing_page' => (bool) ($validated['use_custom_landing_page'] ?? false),
        ]);

        if ($tenant->use_custom_landing_page) {
            $tenantLandingPageService->ensureCustomPageExists($tenant);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Tenant landing page settings updated successfully.');
    }
}
