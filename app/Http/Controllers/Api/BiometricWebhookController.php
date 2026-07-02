<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBiometricAccessEventJob;
use App\Services\Tenancy\TenantDatabaseManager;
use App\Services\TenantConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Receives real-time access control events pushed by a biometric device.
 *
 * Route: POST /api/biometric/events/{tenantDomain}
 *
 * No session auth — the tenant is identified by the URL domain segment and
 * the request is validated by a per-tenant webhook token in the query string.
 *
 * The device must be configured to push events here via:
 *   PUT /ISAPI/Event/notification/httpHosts/1
 */
class BiometricWebhookController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $config,
        private readonly TenantDatabaseManager $tenancy,
    ) {}

    /**
     * POST /api/biometric/events/{tenantDomain}?token={webhookToken}
     */
    public function handle(Request $request, string $tenantDomain): Response
    {
        // 1. Resolve and activate tenant by domain
        $tenant = $this->tenancy->activateByDomain($tenantDomain);

        Log::debug('Biometric real-time push: incoming request', [
            'route_tenant_domain' => $tenantDomain,
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ] : null,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'query' => $this->maskSensitiveData($request->query()),
            'payload' => $this->maskSensitiveData($request->all()),
            // Keep this bounded in case the device posts a large multipart body.
            'raw_body_preview' => mb_substr($request->getContent(), 0, 4000),
        ]);

        if (!$tenant) {
            Log::warning('Biometric real-time push: tenant not found', [
                'route_tenant_domain' => $tenantDomain,
                'ip' => $request->ip(),
            ]);

            return response('', 404);
        }

        // 2. Validate webhook token (constant-time comparison to prevent timing attacks)
        $allConfig = $this->config->all($tenant->id);
        $storedToken = $allConfig['biometric.webhook_token'] ?? '';
        $incoming = (string) $request->query('token', '');

        Log::debug('Biometric real-time push: tenant config loaded', [
            'webhook_enabled' => $allConfig['biometric.webhook_enabled'] ?? '0',
            'has_stored_token' => $storedToken !== '',
            'has_incoming_token' => $incoming !== '',
            'server_host' => $allConfig['biometric.webhook_server_host'] ?? '',
            'server_port' => $allConfig['biometric.webhook_server_port'] ?? '',
        ]);

        if ($storedToken === '' || !hash_equals($storedToken, $incoming)) {
            Log::warning('Biometric real-time push: invalid token', [
                'tenant' => $tenantDomain,
                'ip' => $request->ip(),
                'has_stored_token' => $storedToken !== '',
                'has_incoming_token' => $incoming !== '',
            ]);

            return response('', 401);
        }

        // 3. Only process when webhook is enabled
        if (($allConfig['biometric.webhook_enabled'] ?? '0') !== '1') {
            Log::info('Biometric real-time push: request ignored because feature is disabled', [
                'tenant' => $tenantDomain,
            ]);

            return response('', 200);
        }

        // 4. Parse event payload
        $event = $this->parsePayload($request);

        if (!$event) {
            // Not an access event we handle — acknowledge and discard
            Log::debug('Biometric real-time push: payload ignored after parsing', [
                'content_type' => $request->header('Content-Type'),
                'body_preview' => mb_substr($request->getContent(), 0, 1000),
            ]);

            return response('', 200);
        }

        Log::debug('Biometric real-time push: parsed event', [
            'event' => $this->summariseEvent($event),
        ]);

        // 5. Queue attendance persistence + sync-log write
        try {
            ProcessBiometricAccessEventJob::dispatch($tenant->id, $this->queueSafeEvent($event));

            Log::debug('Biometric real-time push: event queued for sync service', [
                'employee_no' => $event['employeeNoString'] ?? null,
                'minor' => $event['minor'] ?? null,
                'time' => $event['time'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Biometric real-time push: queue dispatch error', [
                'tenant' => $tenantDomain,
                'event' => $this->summariseEvent($event),
                'error' => $e->getMessage(),
            ]);
        }

        return response('', 200);
    }

    /**
     * Parse the device's HTTP push payload into a normalised event array.
     *
     * Supports:
     *  - Raw XML body (Content-Type: application/xml or text/xml)
     *  - Multipart form-data with an XML part (field name: event_log or first text/xml part)
     *  - JSON body (Content-Type: application/json, newer firmware)
     *
     * Returns null if this is not a recognisable access control event.
     */
    private function parsePayload(Request $request): ?array
    {
        $contentType = strtolower($request->header('Content-Type', ''));
        $body = $request->getContent();

        // ── JSON ───────────────────────────────────────────────────────────
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($body, true);
            $alert = $data['EventNotificationAlert'] ?? null;

            if (!$alert) {
                Log::debug('Biometric real-time push: JSON payload missing EventNotificationAlert');

                return null;
            }

            $ace = $alert['AccessControllerEvent'] ?? null;

            if (!$ace) {
                Log::debug('Biometric real-time push: JSON payload missing AccessControllerEvent');

                return null;
            }

            return $this->normalise(
                employeeNo: $ace['employeeNoString'] ?? null,
                eventTime: $ace['eventTime'] ?? ($alert['dateTime'] ?? null),
                minor: (int) ($ace['minorEventType'] ?? 0),
                attendanceStatus: $ace['attendanceStatus'] ?? null,
                name: $ace['name'] ?? null,
                pictureUrl: $ace['pictureURL'] ?? ($alert['pictureURL'] ?? null),
            );
        }

        // ── Multipart form-data ────────────────────────────────────────────
        if (str_contains($contentType, 'multipart/form-data')) {
            // Laravel parses text fields automatically; device may send XML as 'event_log'
            $xmlString = $request->input('event_log') ?? $this->extractXmlFromBody($body);

            if (!$xmlString) {
                Log::debug('Biometric real-time push: multipart payload has no XML event part', [
                    'input_keys' => array_keys($request->all()),
                    'file_keys' => array_keys($request->allFiles()),
                    'body_preview' => mb_substr($body, 0, 1000),
                ]);

                return null;
            }

            $event = $this->parseXml($xmlString);

            // The device attaches the captured face snapshot as a binary part.
            return $event ? $this->attachPicture($event, $request) : null;
        }

        // ── Raw XML (application/xml, text/xml, or unrecognised content-type) ──
        if (str_contains($contentType, 'xml') || str_starts_with(ltrim($body), '<?xml')) {
            return $this->parseXml($body);
        }

        return null;
    }

    /**
     * Parse an XML EventNotificationAlert string into a normalised event array.
     */
    private function parseXml(string $xml): ?array
    {
        try {
            libxml_use_internal_errors(true);
            $node = simplexml_load_string($xml);
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (!$node) {
                Log::debug('Biometric real-time push: XML parse failed', [
                    'errors' => array_map(
                        fn ($error) => trim($error->message),
                        array_slice($errors, 0, 3),
                    ),
                    'xml_preview' => mb_substr($xml, 0, 1000),
                ]);

                return null;
            }

            // Strip namespaces for easier access
            $eventType = (string) ($node->eventType ?? '');

            if ($eventType !== 'AccessControllerEvent') {
                Log::debug('Biometric real-time push: XML event type ignored', [
                    'event_type' => $eventType,
                ]);

                return null;
            }

            $ace = $node->AccessControllerEvent ?? null;

            if (!$ace) {
                Log::debug('Biometric real-time push: XML payload missing AccessControllerEvent node');

                return null;
            }

            return $this->normalise(
                employeeNo: (string) ($ace->employeeNoString ?? ''),
                eventTime: (string) ($ace->eventTime ?? (string) ($node->dateTime ?? '')),
                minor: (int) (string) ($ace->minorEventType ?? 0),
                attendanceStatus: (string) ($ace->attendanceStatus ?? ''),
                name: (string) ($ace->name ?? '') ?: null,
                pictureUrl: (string) ($ace->pictureURL ?? (string) ($node->pictureURL ?? '')) ?: null,
            );
        } catch (\Throwable $e) {
            Log::debug('Biometric real-time push: XML parse exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Attach the captured snapshot (sent as a binary multipart part) to the event.
     *
     * HikVision pushes the face-capture image alongside the XML event part. We
     * read the first image part and pass its raw bytes downstream for storage.
     */
    private function attachPicture(array $event, Request $request): array
    {
        foreach ($request->allFiles() as $file) {
            $files = is_array($file) ? $file : [$file];

            foreach ($files as $uploaded) {
                if (!$uploaded || !$uploaded->isValid()) {
                    continue;
                }

                $mime = (string) $uploaded->getMimeType();
                $isImage = str_starts_with($mime, 'image/')
                    || in_array(strtolower($uploaded->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'], true);

                if (!$isImage) {
                    continue;
                }

                $bytes = @file_get_contents($uploaded->getRealPath());

                if ($bytes !== false && $bytes !== '') {
                    $event['picture_bytes'] = $bytes;
                    $event['picture_content_type'] = $mime ?: 'image/jpeg';

                    Log::debug('Biometric real-time push: attached multipart image', [
                        'mime' => $event['picture_content_type'],
                        'bytes' => strlen($bytes),
                    ]);

                    return $event;
                }
            }
        }

        return $event;
    }

    /**
     * Normalise event fields and return null if required fields are missing.
     */
    private function normalise(
        ?string $employeeNo,
        ?string $eventTime,
        int $minor,
        ?string $attendanceStatus,
        ?string $name = null,
        ?string $pictureUrl = null,
    ): ?array {
        // Authentication events always carry a time. employeeNo may be absent for
        // failed/stranger attempts, so only the time is strictly required.
        if (!$eventTime) {
            Log::debug('Biometric real-time push: normalised event missing event time', [
                'employee_no' => $employeeNo,
                'minor' => $minor,
            ]);

            return null;
        }

        return [
            'employeeNoString' => $employeeNo,
            'time' => $eventTime,
            'minor' => $minor,
            'attendanceStatus' => $attendanceStatus,
            'name' => $name,
            'picture_url' => $pictureUrl,
        ];
    }

    /**
     * Extract the first XML block from a raw multipart body string.
     * Used as a fallback when Laravel doesn't automatically parse the XML part.
     */
    private function extractXmlFromBody(string $body): ?string
    {
        if (preg_match('/<\?xml[\s\S]*?<\/EventNotificationAlert>/i', $body, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Recursively mask sensitive keys before writing request data to logs.
     */
    private function maskSensitiveData(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $masked = [];

        foreach ($value as $key => $item) {
            $keyString = strtolower((string) $key);
            $isSensitive = in_array($keyString, [
                'token',
                'webhook_token',
                'password',
                'pass',
                'secret',
                'api_key',
                'apikey',
                'authorization',
            ], true);

            if ($isSensitive) {
                $masked[$key] = '[masked]';
                continue;
            }

            $masked[$key] = is_array($item)
                ? $this->maskSensitiveData($item)
                : $item;
        }

        return $masked;
    }

    /**
     * Keep event logs useful without dumping binary image data.
     */
    private function summariseEvent(array $event): array
    {
        $summary = $event;

        if (isset($summary['picture_bytes'])) {
            $summary['picture_bytes_length'] = is_string($summary['picture_bytes'])
                ? strlen($summary['picture_bytes'])
                : null;
            unset($summary['picture_bytes']);
        }

        return $summary;
    }

    /**
     * Keep queued payloads JSON-safe; raw multipart image bytes can contain
     * invalid UTF-8 and break Laravel's database queue payload encoding.
     */
    private function queueSafeEvent(array $event): array
    {
        if (isset($event['picture_bytes']) && is_string($event['picture_bytes'])) {
            $event['picture_bytes_base64'] = base64_encode($event['picture_bytes']);
            unset($event['picture_bytes']);
        }

        return $event;
    }
}
