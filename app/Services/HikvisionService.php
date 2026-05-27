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

    // -------------------------------------------------------------------------
    // Internal HTTP helpers
    // -------------------------------------------------------------------------

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
