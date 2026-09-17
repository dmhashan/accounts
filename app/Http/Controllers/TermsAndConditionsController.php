<?php

namespace App\Http\Controllers;

use App\Services\TenantConfigurationService;
use Illuminate\Http\Request;

class TermsAndConditionsController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $configService,
    ) {}

    public function show(Request $request)
    {
        $tenant = app('tenant');

        if (!$tenant || !$tenant->is_active) {
            abort(404);
        }

        $config = $this->configService->all($tenant->id);
        $enabled = ($config['terms.enabled'] ?? '0') === '1';
        $content = $config['terms.content'] ?? '';

        if (!$enabled) {
            return response()->view('members.terms-unavailable', [
                'tenant' => $tenant,
            ], 404);
        }

        return view('members.terms-and-conditions', [
            'tenant' => $tenant,
            'content' => $content,
        ]);
    }
}
