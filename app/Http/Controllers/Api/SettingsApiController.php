<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunLegacyCommand;
use App\Models\CommandRunLog;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsApiController extends Controller
{
    public function __construct(
        private readonly MediaStorageService $media,
    ) {}

    public function general(): JsonResponse
    {
        $tenant = app('tenant');

        return response()->json([
            'data' => [
                'name' => $tenant->name,
                'address' => $tenant->address,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'logo_url' => $tenant->logo_path ? $this->media->url($tenant->logo_path) : null,
            ],
        ]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $tenant = app('tenant');

        $tenant->update([
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => [
                'name' => $tenant->name,
                'address' => $tenant->address,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'logo_url' => $tenant->logo_path ? $this->media->url($tenant->logo_path) : null,
            ],
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp,svg'],
        ]);

        $tenant = app('tenant');

        if ($tenant->logo_path) {
            $this->media->delete($tenant->logo_path);
        }

        $path = $this->media->store($request->file('logo'), 'tenant-logos');

        $tenant->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo uploaded successfully.',
            'logo_url' => $this->media->url($path),
        ]);
    }

    public function deleteLogo(): JsonResponse
    {
        $tenant = app('tenant');

        if ($tenant->logo_path) {
            $this->media->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        return response()->json(['message' => 'Logo removed successfully.']);
    }

    public function runLegacyTool(Request $request): JsonResponse
    {
        $allowed = ['legacy:sync-members', 'legacy:sync-attendance', 'legacy:sync-payments'];

        $command = $request->input('command');

        $baseRules = [
            'command' => ['required', 'string', 'in:' . implode(',', $allowed)],
            'access_token' => ['required', 'string', 'min:10'],
        ];

        $commandRules = match ($command) {
            'legacy:sync-attendance' => [
                'date_start' => ['required', 'date_format:Y-m-d'],
                'date_end' => ['required', 'date_format:Y-m-d', 'gte:date_start'],
            ],
            'legacy:sync-payments' => [
                'date_start' => ['nullable', 'date_format:Y-m-d'],
                'date_end' => ['nullable', 'date_format:Y-m-d'],
                'account_name' => ['nullable', 'string', 'max:255'],
                'page_size' => ['nullable', 'integer', 'min:1', 'max:500'],
            ],
            default => [],
        };

        $request->validate(array_merge($baseRules, $commandRules));

        $tenant = app('tenant');

        $params = [
            '--access-token' => $request->input('access_token'),
            '--tenant-domain' => $tenant->domain,
        ];

        if ($command === 'legacy:sync-attendance') {
            $params['--date-start'] = $request->input('date_start');
            $params['--date-end'] = $request->input('date_end');
        } elseif ($command === 'legacy:sync-payments') {
            if ($request->filled('date_start')) {
                $params['--date-start'] = $request->input('date_start');
            }

            if ($request->filled('date_end')) {
                $params['--date-end'] = $request->input('date_end');
            }

            if ($request->filled('account_name')) {
                $params['--account-name'] = $request->input('account_name');
            }

            if ($request->filled('page_size')) {
                $params['--page-size'] = $request->input('page_size');
            }
        }
        // legacy:sync-members: no date options; no extra params needed

        $log = CommandRunLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()?->id,
            'command' => $command,
            'params' => $this->safeLogParams($params),
            'exit_code' => null,
            'output' => null,
            'success' => null,
        ]);

        RunLegacyCommand::dispatch($log->id, $command, $params);

        return response()->json([
            'queued' => true,
            'log_id' => $log->id,
        ]);
    }

    public function legacyToolLogs(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $logs = CommandRunLog::where('tenant_id', $tenant->id)
            ->when($request->filled('command'), fn ($q) => $q->where('command', $request->input('command')))
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'command' => $log->command,
                'params' => $log->params,
                'exit_code' => $log->exit_code,
                'output' => $log->output,
                'success' => $log->success,
                'user' => $log->user?->name,
                'created_at' => $log->created_at->toDateTimeString(),
            ]);

        return response()->json(['data' => $logs]);
    }

    private function safeLogParams(array $params): array
    {
        $safe = $params;
        unset($safe['--access-token']);

        return $safe;
    }
}
