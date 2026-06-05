<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
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
        private readonly BiometricSyncService $biometric,
        private readonly TenantConfigurationService $config,
    ) {}

    /**
     * POST /api/biometric/events/{tenantDomain}?token={webhookToken}
     */
    public function handle(Request $request, string $tenantDomain): Response
    {
        // 1. Resolve tenant by domain
        $tenant = Tenant::where('domain', $tenantDomain)->first();

        if (!$tenant) {
            return response('', 404);
        }

        // The webhook route has no IdentifyTenant middleware, so bind the tenant
        // into the container now — MediaStorageService relies on app('tenant')
        // when storing the captured authentication snapshot.
        app()->instance('tenant', $tenant);

        // 2. Validate webhook token (constant-time comparison to prevent timing attacks)
        $allConfig = $this->config->all($tenant->id);
        $storedToken = $allConfig['biometric.webhook_token'] ?? '';
        $incoming = (string) $request->query('token', '');

        if ($storedToken === '' || !hash_equals($storedToken, $incoming)) {
            Log::warning('BiometricWebhook: invalid token', [
                'tenant' => $tenantDomain,
                'ip' => $request->ip(),
            ]);

            return response('', 401);
        }

        // 3. Only process when webhook is enabled
        if (($allConfig['biometric.webhook_enabled'] ?? '0') !== '1') {
            return response('', 200);
        }

        // 4. Parse event payload
        $event = $this->parsePayload($request);

        if (!$event) {
            // Not an access event we handle — acknowledge and discard
            return response('', 200);
        }

        // 5. Persist attendance + write sync log
        try {
            $this->biometric->handleIncomingEvent($tenant, $event);
        } catch (\Throwable $e) {
            Log::error('BiometricWebhook: persist error', [
                'tenant' => $tenantDomain,
                'event' => $event,
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
                return null;
            }

            $ace = $alert['AccessControllerEvent'] ?? null;

            if (!$ace) {
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
            libxml_clear_errors();

            if (!$node) {
                return null;
            }

            // Strip namespaces for easier access
            $eventType = (string) ($node->eventType ?? '');

            if ($eventType !== 'AccessControllerEvent') {
                return null;
            }

            $ace = $node->AccessControllerEvent ?? null;

            if (!$ace) {
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
        } catch (\Throwable) {
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
}
