<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HikVision ISAPI client.
 *
 * Communicates with a single HikVision device using HTTP Digest authentication.
 * All methods return a normalised array with keys:
 *   - success   bool
 *   - data      array  (parsed device response)
 *   - message   string (human-readable status)
 *
 * Errors are never thrown — callers should inspect 'success'.
 */
class HikvisionService
{
    private string $baseUrl;

    public function __construct(
        private readonly string $ip,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
    ) {
        $scheme = $this->port === 443 ? 'https' : 'http';
        $this->baseUrl = "{$scheme}://{$this->ip}:{$this->port}";
    }

    // -------------------------------------------------------------------------
    // Connection test
    // -------------------------------------------------------------------------

    /**
     * Test connectivity by fetching basic device info.
     */
    public function testConnection(): array
    {
        return $this->get('/ISAPI/System/deviceInfo');
    }

    // -------------------------------------------------------------------------
    // Person management
    // -------------------------------------------------------------------------

    /**
     * Add a new person to the device.
     *
     * @param  array{employeeNo: string, name: string, userType: string, Valid: array, doorRight: string, RightPlan: array}  $person
     */
    public function addPerson(array $person): array
    {
        return $this->post('/ISAPI/AccessControl/UserInfo/Record', ['UserInfo' => $person]);
    }

    /**
     * Update an existing person on the device (partial update).
     */
    public function updatePerson(array $person): array
    {
        return $this->put('/ISAPI/AccessControl/UserInfo/Modify', ['UserInfo' => $person]);
    }

    /**
     * Delete a person by their employeeNo.
     */
    public function deletePerson(string $employeeNo): array
    {
        return $this->put('/ISAPI/AccessControl/UserInfo/Delete', [
            'UserInfoDelCond' => [
                'EmployeeNoList' => [
                    ['employeeNo' => $employeeNo],
                ],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Attendance / access events
    // -------------------------------------------------------------------------

    /**
     * Query access control events (attendance) from the device.
     *
     * @param  string  $startTime  ISO 8601 e.g. 2026-05-01T00:00:00
     * @param  string  $endTime  ISO 8601
     * @param  int  $offset  Pagination offset (0-based)
     * @param  int  $maxResults  Page size (max 100)
     */
    public function getAttendanceEvents(string $startTime, string $endTime, int $offset = 0, int $maxResults = 50): array
    {
        return $this->post('/ISAPI/AccessControl/AcsEvent', [
            'AcsEventCond' => [
                'searchID' => 'biometric-sync-' . now()->timestamp,
                'searchResultPosition' => $offset,
                'maxResults' => $maxResults,
                'major' => 5,  // Access control events only
                'startTime' => $startTime,
                'endTime' => $endTime,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Member device-record queries
    // -------------------------------------------------------------------------

    /**
     * Fetch a single person's record from the device by employeeNo.
     */
    public function getUserInfo(string $employeeNo): array
    {
        return $this->post('/ISAPI/AccessControl/UserInfo/Search', [
            'UserInfoSearchCond' => [
                'searchID' => 'info-' . now()->timestamp,
                'searchResultPosition' => 0,
                'maxResults' => 1,
                'EmployeeNoList' => [['employeeNo' => $employeeNo]],
            ],
        ]);
    }

    /**
     * Fetch face-enrolment info for a person from the device.
     */
    public function getFaceInfo(string $employeeNo): array
    {
        return $this->post('/ISAPI/AccessControl/Face/Search', [
            'FaceInfoCond' => [
                'searchID' => 'face-' . now()->timestamp,
                'searchResultPosition' => 0,
                'maxResults' => 10,
                'EmployeeNoList' => [['employeeNo' => $employeeNo]],
            ],
        ]);
    }

    /**
     * Fetch fingerprint-enrolment info for a person from the device.
     */
    public function getFingerprintInfo(string $employeeNo): array
    {
        return $this->post('/ISAPI/AccessControl/Fingerprint/Search', [
            'FingerPrintCond' => [
                'searchID' => 'fp-' . now()->timestamp,
                'searchResultPosition' => 0,
                'maxResults' => 10,
                'EmployeeNoList' => [['employeeNo' => $employeeNo]],
            ],
        ]);
    }

    /**
     * Fetch card-assignment info for a person from the device.
     */
    public function getCardInfo(string $employeeNo): array
    {
        return $this->post('/ISAPI/AccessControl/CardInfo/Search', [
            'CardInfoSearchCond' => [
                'searchID' => 'card-' . now()->timestamp,
                'searchResultPosition' => 0,
                'maxResults' => 10,
                'EmployeeNoList' => [['employeeNo' => $employeeNo]],
            ],
        ]);
    }

    /**
     * Trigger fingerprint enrolment on the device for a person.
     * The device enters collection mode and prompts the person to scan.
     *
     * @param  int  $fingerNo  0–9 (finger slot index)
     */
    public function setupFingerprint(string $employeeNo, int $fingerNo = 0): array
    {
        return $this->put('/ISAPI/AccessControl/Fingerprint/SetUp', [
            'FingerPrint' => [
                'employeeNo' => $employeeNo,
                'fingerNo' => $fingerNo,
                'fingerType' => 'normalFinger',
                'deleteFingerPrint' => false,
            ],
        ]);
    }

    /**
     * Fetch an absolute URL from the device (e.g. a face image) using digest auth.
     * Returns raw body bytes and content-type so the caller can proxy the response.
     */
    public function proxyImage(string $url): array
    {
        try {
            $response = Http::withDigestAuth($this->username, $this->password)
                ->timeout(10)
                ->withoutVerifying()
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'body' => $response->body(),
                    'content_type' => $response->header('Content-Type') ?: 'image/jpeg',
                ];
            }

            return ['success' => false, 'body' => '', 'content_type' => ''];
        } catch (ConnectionException $e) {
            return ['success' => false, 'body' => '', 'content_type' => ''];
        }
    }

    // -------------------------------------------------------------------------
    // Real-time event push (HTTP notification / webhook)
    // -------------------------------------------------------------------------

    /**
     * Configure the device to push real-time access events to our server.
     *
     * DS-K1T320 (Value Series) requires XML for the httpHosts endpoint — JSON
     * is rejected with "Invalid Format". We send raw XML and strip ?format=json.
     *
     * @param  string  $host  Our server IP or hostname reachable from the device
     * @param  int  $port  Our server port (typically 80 or 443)
     * @param  string  $path  URL path + query, e.g. /api/biometric/events/gymname?token=xxx
     */
    public function configureHttpNotification(string $host, int $port, string $path): array
    {
        // Detect whether $host is a hostname (not a bare IPv4) so we can set
        // addressingFormatType correctly. The device rejects 'ipaddress' for hostnames.
        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $addrType = $isIp ? 'ipaddress' : 'hostname';
        $addrTag = $isIp ? "<ipAddress>{$host}</ipAddress>" : "<hostName>{$host}</hostName>";

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <HttpHostNotification version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
              <id>1</id>
              <url><![CDATA[{$path}]]></url>
              <protocolType>HTTP</protocolType>
              <parameterFormatType>XML</parameterFormatType>
              <addressingFormatType>{$addrType}</addressingFormatType>
              {$addrTag}
              <portNo>{$port}</portNo>
              <httpAuthenticationMethod>none</httpAuthenticationMethod>
              <heartbeatInterval>30</heartbeatInterval>
              <heartbeatIntervalEffectiveTime>60</heartbeatIntervalEffectiveTime>
            </HttpHostNotification>
            XML;

        return $this->putXml('/ISAPI/Event/notification/httpHosts/1', $xml);
    }

    /**
     * Read the current HTTP notification host configuration from the device.
     * Returns parsed fields under data['HttpHostNotification'].
     */
    public function getHttpNotificationConfig(): array
    {
        return $this->getXml('/ISAPI/Event/notification/httpHosts/1');
    }

    /**
     * Disable HTTP event push by zeroing out the notification host.
     */
    public function disableHttpNotification(): array
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <HttpHostNotification version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
              <id>1</id>
              <url>/</url>
              <protocolType>HTTP</protocolType>
              <parameterFormatType>XML</parameterFormatType>
              <addressingFormatType>ipaddress</addressingFormatType>
              <ipAddress>0.0.0.0</ipAddress>
              <portNo>80</portNo>
              <httpAuthenticationMethod>none</httpAuthenticationMethod>
              <heartbeatInterval>0</heartbeatInterval>
            </HttpHostNotification>
            XML;

        return $this->putXml('/ISAPI/Event/notification/httpHosts/1', $xml);
    }

    // -------------------------------------------------------------------------
    // Internal HTTP helpers
    // -------------------------------------------------------------------------

    /**
     * PUT with a raw XML body (no ?format=json — device rejects it on some endpoints).
     * Parses the XML response and normalises it to the standard ['success','data','message'] shape.
     */
    private function putXml(string $path, string $xmlBody): array
    {
        try {
            Log::debug('HikVision PUT (XML)', ['url' => $this->baseUrl . $path]);

            $response = Http::withDigestAuth($this->username, $this->password)
                ->timeout(15)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/xml'])
                ->withBody(trim($xmlBody), 'application/xml')
                ->put($this->baseUrl . $path);

            Log::debug('HikVision PUT (XML) response', ['status' => $response->status(), 'body' => $response->body()]);

            return $this->parseXmlResponse($response);
        } catch (ConnectionException $e) {
            Log::warning('HikVision connection error (XML PUT)', ['ip' => $this->ip, 'path' => $path, 'error' => $e->getMessage()]);

            return ['success' => false, 'data' => [], 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * GET that returns XML, parsed into ['success','data','message'].
     * data['HttpHostNotification'] contains the notification config fields.
     */
    private function getXml(string $path): array
    {
        try {
            $response = Http::withDigestAuth($this->username, $this->password)
                ->timeout(15)
                ->withoutVerifying()
                ->withHeaders(['Accept' => 'application/xml'])
                ->get($this->baseUrl . $path);

            return $this->parseXmlResponse($response);
        } catch (ConnectionException $e) {
            Log::warning('HikVision connection error (XML GET)', ['ip' => $this->ip, 'error' => $e->getMessage()]);

            return ['success' => false, 'data' => [], 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Parse an XML response from the device into the standard result array.
     * Handles both status responses (ResponseStatus) and data responses (e.g. HttpHostNotification).
     */
    private function parseXmlResponse(\Illuminate\Http\Client\Response $response): array
    {
        $body = $response->body();

        if (!$response->successful()) {
            // Try to extract an error message from the XML body
            $msg = $this->extractXmlStatusString($body) ?? ('HTTP ' . $response->status());

            return ['success' => false, 'data' => [], 'message' => $msg];
        }

        // An empty 200 body counts as success (device accepted the config)
        if (trim($body) === '') {
            return ['success' => true, 'data' => [], 'message' => 'OK'];
        }

        libxml_use_internal_errors(true);
        $node = simplexml_load_string($body);
        libxml_clear_errors();

        if (!$node) {
            // Body present but not parseable — if HTTP was 2xx, treat as success
            return ['success' => true, 'data' => [], 'message' => 'OK'];
        }

        $localName = $node->getName();

        // ResponseStatus node means the device is reporting an explicit status code
        if ($localName === 'ResponseStatus') {
            $statusCode = (int) (string) ($node->statusCode ?? 0);
            $statusStr = (string) ($node->statusString ?? '');
            $isOk = $statusCode === 1 || strtolower($statusStr) === 'ok';

            return [
                'success' => $isOk,
                'data' => ['statusCode' => $statusCode, 'statusString' => $statusStr,
                    'subStatusCode' => (string) ($node->subStatusCode ?? '')],
                'message' => $statusStr ?: ($isOk ? 'OK' : 'Device error'),
            ];
        }

        // Data response (e.g. HttpHostNotification) — flatten to array and return
        $data = $this->xmlToArray($node);

        return ['success' => true, 'data' => [$localName => $data], 'message' => 'OK'];
    }

    /** Extract statusString from an XML body string without fully parsing it. */
    private function extractXmlStatusString(string $body): ?string
    {
        if (preg_match('/<statusString>([^<]+)<\/statusString>/i', $body, $m)) {
            return $m[1];
        }

        return null;
    }

    /** Recursively convert a SimpleXMLElement to a plain array. */
    private function xmlToArray(\SimpleXMLElement $node): array
    {
        $result = [];

        foreach ($node->children() as $child) {
            $name = $child->getName();
            $children = $child->children();
            $result[$name] = count($children) > 0 ? $this->xmlToArray($child) : (string) $child;
        }

        return $result;
    }

    private function get(string $path): array
    {
        try {
            $response = Http::withDigestAuth($this->username, $this->password)
                ->timeout(15)
                ->withoutVerifying()
                ->get($this->baseUrl . $path . '?format=json');

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            Log::warning('HikVision connection error', ['ip' => $this->ip, 'error' => $e->getMessage()]);

            return ['success' => false, 'data' => [], 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    private function post(string $path, array $body): array
    {
        try {
            Log::debug('HikVision POST', ['url' => $this->baseUrl . $path, 'body' => $body]);

            $response = Http::withDigestAuth($this->username, $this->password)
                ->timeout(15)
                ->withoutVerifying()
                ->post($this->baseUrl . $path . '?format=json', $body);

            $result = $this->parseResponse($response);
            Log::debug('HikVision POST response', ['status' => $response->status(), 'body' => $response->body()]);

            return $result;
        } catch (ConnectionException $e) {
            Log::warning('HikVision connection error', ['ip' => $this->ip, 'path' => $path, 'error' => $e->getMessage()]);

            return ['success' => false, 'data' => [], 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    private function put(string $path, array $body): array
    {
        try {
            Log::debug('HikVision PUT', ['url' => $this->baseUrl . $path, 'body' => $body]);

            $response = Http::withDigestAuth($this->username, $this->password)
                ->timeout(15)
                ->withoutVerifying()
                ->put($this->baseUrl . $path . '?format=json', $body);

            $result = $this->parseResponse($response);
            Log::debug('HikVision PUT response', ['status' => $response->status(), 'body' => $response->body()]);

            return $result;
        } catch (ConnectionException $e) {
            Log::warning('HikVision connection error', ['ip' => $this->ip, 'path' => $path, 'error' => $e->getMessage()]);

            return ['success' => false, 'data' => [], 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    private function parseResponse(\Illuminate\Http\Client\Response $response): array
    {
        $data = $response->json() ?? [];

        // HikVision success: statusCode === 1 or HTTP 2xx with no statusCode key (device info etc.)
        $statusCode = $data['statusCode'] ?? null;
        $isOk = $response->successful() && ($statusCode === null || $statusCode === 1);

        return [
            'success' => $isOk,
            'data' => $data,
            'message' => $data['statusString'] ?? ($isOk ? 'OK' : 'Device error'),
        ];
    }
}
