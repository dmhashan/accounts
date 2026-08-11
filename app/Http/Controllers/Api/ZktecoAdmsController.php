<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiometricAccessEvent;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Services\BiometricSyncService;
use App\Services\MediaStorageService;
use App\Services\Tenancy\TenantDatabaseManager;
use App\Services\TenantConfigurationService;
use App\Services\ZktecoAdmsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ZktecoAdmsController
 *
 * Isolated public controller implementing ZKTeco ADMS (Cloud Server / Push Protocol).
 *
 * Base Routes:
 * - ANY  /iclock/cdata
 * - GET  /iclock/getrequest
 * - POST /iclock/devicecmd
 * - POST /iclock/fdata
 * - ANY  /iclock/ping
 * - ANY  /iclock/registry
 */
class ZktecoAdmsController extends Controller
{
    public function __construct(
        private readonly ZktecoAdmsService $zkteco,
        private readonly BiometricSyncService $biometric,
        private readonly TenantConfigurationService $config,
        private readonly TenantDatabaseManager $tenancy,
        private readonly MediaStorageService $media,
    ) {}

    /**
     * Resolve tenant context by Device Serial Number (SN) or domain.
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');

        // 1. Try subdomain resolution first if accessed on tenant subdomain
        $domain = $this->tenancy->domainForRequest($request);

        if ($domain && !in_array($domain, ['biometric', 'biomatric', 'adms'], true)) {
            $tenant = $this->tenancy->activateByDomain($domain);

            if ($tenant) {
                return $tenant;
            }
        }

        // 2. Resolve by Serial Number from tenant_configurations
        if ($sn !== '') {
            $configRow = TenantConfiguration::query()
                ->where('key', 'biometric.device_sn')
                ->where('value', $sn)
                ->first();

            if ($configRow && $configRow->tenant_id) {
                $tenant = Tenant::find($configRow->tenant_id);

                if ($tenant) {
                    return $this->tenancy->activateByDomain($tenant->domain) ?: $tenant;
                }
            }
        }

        // 3. Fallback: Check if single tenant environment or bypass domain configured
        if (!config('app.multitenancy_enabled', true)) {
            $bypassDomain = (string) config('app.multitenancy_bypass_domain');

            if ($bypassDomain) {
                return $this->tenancy->activateByDomain($bypassDomain);
            }
        }

        return null;
    }

    /**
     * GET/POST /iclock/cdata
     *
     * GET: Handshake / configuration request.
     * POST: Data push (table=ATTLOG for attendance logs, table=options for device parameters).
     */
    public function cdata(Request $request): Response
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');
        $tenant = $this->resolveTenant($request);

        Log::debug('ZKTeco ADMS cdata received', [
            'method' => $request->method(),
            'sn' => $sn,
            'table' => $request->query('table'),
            'tenant' => $tenant ? $tenant->domain : null,
            'query' => $request->query(),
        ]);

        if ($request->isMethod('get')) {
            // Handshake & options request from device
            if ($tenant) {
                $this->zkteco->recordHeartbeat($sn);
            }

            $responseBody = $this->zkteco->buildConfigResponse($sn, $request->query());

            return response($responseBody, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        // POST handling
        $table = strtoupper((string) $request->query('table', ''));
        $rawContent = $request->getContent();

        if ($tenant) {
            $this->zkteco->recordHeartbeat($sn);
        }

        if ($table === 'ATTLOG') {
            // Real-time or batch attendance log push
            $events = $this->zkteco->parseAttendanceLogs($rawContent);
            $processedCount = 0;

            if ($tenant) {
                foreach ($events as $event) {
                    try {
                        $this->biometric->handleIncomingEvent($tenant, $event);
                        $processedCount++;
                    } catch (\Throwable $e) {
                        Log::warning('ZKTeco ADMS: failed to ingest event', [
                            'event' => $event,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return response("OK: {$processedCount}\r\n", 200, ['Content-Type' => 'text/plain']);
        }

        if ($table === 'OPTIONS') {
            // Device parameter / capability push
            $options = $this->parseOptionsPayload($rawContent);

            if ($tenant) {
                $this->zkteco->recordHeartbeat($sn, $options);
            }

            return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
        }

        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * GET /iclock/getrequest?SN={SN}
     *
     * Device polls for pending commands.
     * Returns "C:{id}:{command}\n..." or "OK".
     */
    public function getrequest(Request $request): Response
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');
        $tenant = $this->resolveTenant($request);

        if (!$tenant || $sn === '') {
            return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
        }

        $this->zkteco->recordHeartbeat($sn);

        $commandsText = $this->zkteco->getPendingCommandsString($sn);

        return response($commandsText, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * POST /iclock/devicecmd?SN={SN}
     *
     * Device reports execution result for queued commands.
     * Payload format: ID={id}&Return={ret}&CMD={cmd}
     */
    public function devicecmd(Request $request): Response
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');
        $tenant = $this->resolveTenant($request);
        $rawContent = $request->getContent();

        Log::debug('ZKTeco ADMS devicecmd result received', [
            'sn' => $sn,
            'tenant' => $tenant ? $tenant->domain : null,
            'payload' => $rawContent,
        ]);

        if ($tenant && $sn !== '') {
            $this->zkteco->recordHeartbeat($sn);
            $this->processDeviceCmdPayload($sn, $rawContent);
        }

        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * POST /iclock/fdata?SN={SN}&table=ATTPHOTO&PIN={pin}
     *
     * Device uploads captured attendance snapshots or user face photos.
     */
    public function fdata(Request $request): Response
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');
        $pin = (string) ($request->query('PIN') ?: $request->input('PIN') ?: '');
        $table = strtoupper((string) ($request->query('table') ?: $request->input('table') ?: ''));
        $tenant = $this->resolveTenant($request);

        Log::debug('ZKTeco ADMS fdata snapshot received', [
            'sn' => $sn,
            'pin' => $pin,
            'table' => $table,
            'tenant' => $tenant ? $tenant->domain : null,
            'files' => array_keys($request->allFiles()),
        ]);

        if ($tenant && $sn !== '') {
            $this->zkteco->recordHeartbeat($sn);

            $file = $request->file('file') ?: $request->file('photo');
            $content = null;

            if ($file && $file->isValid()) {
                $content = file_get_contents($file->getRealPath());
            } elseif ($request->getContent()) {
                $content = $request->getContent();
            }

            if ($content && strlen($content) > 100) {
                $cleanPin = $this->zkteco->extractPin($pin);
                $path = 'biometric-snapshots/zk_' . $cleanPin . '_' . time() . '_' . Str::random(6) . '.jpg';
                $storedPath = $this->media->storeContent($content, $path);

                // Attach picture to the most recent matching access event
                if ($cleanPin) {
                    $recentEvent = BiometricAccessEvent::query()
                        ->where('employee_no', $cleanPin)
                        ->whereNull('picture_path')
                        ->where('created_at', '>=', now()->subMinutes(5))
                        ->latest('created_at')
                        ->first();

                    if ($recentEvent) {
                        $recentEvent->update(['picture_path' => $storedPath]);
                    }
                }
            }
        }

        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * ANY /iclock/ping
     */
    public function ping(Request $request): Response
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');
        $tenant = $this->resolveTenant($request);

        if ($tenant && $sn !== '') {
            $this->zkteco->recordHeartbeat($sn);
        }

        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * ANY /iclock/registry
     */
    public function registry(Request $request): Response
    {
        $sn = (string) ($request->query('SN') ?: $request->input('SN') ?: '');
        $tenant = $this->resolveTenant($request);

        if ($tenant && $sn !== '') {
            $this->zkteco->recordHeartbeat($sn);
        }

        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Parse key-value device parameters from options table push.
     */
    private function parseOptionsPayload(string $raw): array
    {
        $options = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $pos = strpos($line, '=');

            if ($pos !== false) {
                $key = ltrim(substr($line, 0, $pos), '~');
                $val = substr($line, $pos + 1);
                $options[$key] = $val;
            }
        }

        return $options;
    }

    /**
     * Parse and process device command return lines.
     * ID={id}&Return={ret}&CMD={cmd}
     */
    private function processDeviceCmdPayload(string $sn, string $raw): void
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            parse_str($line, $parsed);

            $id = isset($parsed['ID']) ? (int) $parsed['ID'] : 0;
            $return = isset($parsed['Return']) ? (int) $parsed['Return'] : -1;
            $cmd = $parsed['CMD'] ?? null;

            if ($id > 0) {
                $this->zkteco->handleDeviceCommandResult($sn, $id, $return, $cmd);
            }
        }
    }
}
