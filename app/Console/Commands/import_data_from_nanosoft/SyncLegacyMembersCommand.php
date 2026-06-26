<?php

namespace App\Console\Commands\import_data_from_nanosoft;

use App\Models\Member;
use App\Models\PaymentPlan;
use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncLegacyMembersCommand extends Command
{
    protected $signature = 'legacy:sync-members
        {--access-token= : Existing bearer access token}
        {--code= : OAuth authorization code}
        {--code-verifier= : OAuth PKCE code verifier}
        {--redirect-uri= : OAuth redirect URI used when obtaining code}
        {--auth-url=https://auth.nanosoft.lk/auth/realms/gymrelm/protocol/openid-connect/token : OAuth token endpoint}
        {--client-id=gym-application-frontend : OAuth client ID}
        {--tenant-id= : Target tenant ID}
        {--tenant-domain= : Target tenant domain}
        {--base-url=https://gm-api.nanosoft.lk/api/gym : Legacy API base URL}
        {--size=100 : Page size}
        {--start-page=1 : Starting page number}
        {--max-pages=0 : Max pages to process, 0 for all}
        {--searchParam= : Legacy search param}
        {--dueDate= : Legacy due date filter}
        {--gender= : Legacy gender filter}
        {--isActive=true : Legacy isActive filter}
        {--planId= : Legacy plan ID filter}';

    protected $description = 'Sync members and linked users from legacy gym API';

    public function handle(): int
    {
        $token = $this->resolveAccessToken();

        if (!$token) {
            return self::FAILURE;
        }

        $tenant = $this->resolveTenant();

        if (!$tenant) {
            $this->error('Tenant not found. Provide --tenant-id or --tenant-domain.');

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        $page = max((int) $this->option('start-page'), 1);
        $size = max((int) $this->option('size'), 1);
        $maxPages = max((int) $this->option('max-pages'), 0);

        $this->info("Sync started for tenant {$tenant->id} ({$tenant->domain})");

        $createdMembers = 0;
        $updatedMembers = 0;
        $skipped = 0;
        $processedPages = 0;

        while (true) {
            if ($maxPages > 0 && $processedPages >= $maxPages) {
                break;
            }

            $query = [
                'page' => $page,
                'size' => $size,
                'searchParam' => (string) $this->option('searchParam'),
                'dueDate' => (string) $this->option('dueDate'),
                'gender' => (string) $this->option('gender'),
                'isActive' => (string) $this->option('isActive'),
                'planId' => (string) $this->option('planId'),
            ];

            $listResponse = $this->requestLegacyWithRetry(
                $token,
                "{$baseUrl}/getmembers",
                $query,
            );

            if (!$listResponse) {
                $this->error("Failed list request on page {$page}: request timeout after retries.");

                return self::FAILURE;
            }

            if (!$listResponse->successful()) {
                $this->error("Failed list request on page {$page}. HTTP {$listResponse->status()}");

                return self::FAILURE;
            }

            $items = $this->extractListItems($listResponse->json());

            if (count($items) === 0) {
                $this->line("No items on page {$page}. Stopping.");
                break;
            }

            $this->line("Processing page {$page} with " . count($items) . ' members...');

            foreach ($items as $summary) {
                if (!is_array($summary)) {
                    $skipped++;
                    continue;
                }

                $legacyId = $this->pick($summary, [
                    'id',
                    'memberId',
                    'member_id',
                    'memberUuid',
                    'member_uuid',
                    'uuid',
                    'guid',
                ]);

                if (!$legacyId) {
                    $skipped++;
                    continue;
                }

                $detailResponse = $this->requestLegacyWithRetry(
                    $token,
                    "{$baseUrl}/getmemberview/{$legacyId}",
                );

                if (!$detailResponse) {
                    $this->warn("Skipping {$legacyId}: detail request timeout after retries");
                    $skipped++;
                    continue;
                }

                if (!$detailResponse->successful()) {
                    $this->warn("Skipping {$legacyId}: detail HTTP {$detailResponse->status()}");
                    $skipped++;
                    continue;
                }

                $detail = $this->extractMemberDetail($detailResponse->json());

                if (!is_array($detail) || $detail === []) {
                    $this->warn("Skipping {$legacyId}: invalid detail payload");
                    $skipped++;
                    continue;
                }

                $result = $this->upsertFromDetail($tenant, $detail, (string) $legacyId);

                $processedMemberId = $result['biometric_member_id'] ?? (string) $legacyId;
                $this->line("{$processedMemberId} - completed");

                if ($result['member'] === 'created') {
                    $createdMembers++;
                } elseif ($result['member'] === 'updated') {
                    $updatedMembers++;
                } else {
                    $skipped++;
                }
            }

            $processedPages++;
            $page++;
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Pages Processed', (string) $processedPages],
            ['Members Created', (string) $createdMembers],
            ['Members Updated', (string) $updatedMembers],
            ['Skipped', (string) $skipped],
        ]);

        $this->info('Legacy member sync completed.');

        return self::SUCCESS;
    }

    private function resolveAccessToken(): ?string
    {
        $accessToken = trim((string) $this->option('access-token'));

        if ($accessToken !== '') {
            return $accessToken;
        }

        $code = trim((string) $this->option('code'));
        $codeVerifier = trim((string) $this->option('code-verifier'));
        $redirectUri = trim((string) $this->option('redirect-uri'));
        $authUrl = trim((string) $this->option('auth-url'));
        $clientId = trim((string) $this->option('client-id'));

        if ($code === '' || $codeVerifier === '' || $redirectUri === '') {
            $this->error('Provide either --access-token OR all of --code, --code-verifier, --redirect-uri.');

            return null;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->post($authUrl, [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => $codeVerifier,
            ]);

        if (!$response->successful()) {
            $this->error('Failed to authenticate and get access token. HTTP ' . $response->status());
            $this->line((string) $response->body());

            return null;
        }

        $token = $response->json('access_token');

        if (!is_string($token) || trim($token) === '') {
            $this->error('Authentication succeeded but access_token is missing.');

            return null;
        }

        return $token;
    }

    private function requestLegacyWithRetry(string $token, string $url, array $query = []): ?Response
    {
        $attempts = 3;
        $delayMicroseconds = 500000;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return Http::acceptJson()
                    ->withToken($token)
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->get($url, $query);
            } catch (\Throwable $exception) {
                if ($attempt === $attempts) {
                    return null;
                }

                usleep($delayMicroseconds);
            }
        }

        return null;
    }

    private function resolveTenant(): ?Tenant
    {
        $tenancy = app(TenantDatabaseManager::class);
        $tenantId = $this->option('tenant-id');

        if ($tenantId !== null && $tenantId !== '') {
            return $tenancy->activateById((int) $tenantId);
        }

        $tenantDomain = trim((string) $this->option('tenant-domain'));

        if ($tenantDomain !== '') {
            return $tenancy->activateByDomain($tenantDomain);
        }

        $bypassDomain = (string) config('app.multitenancy_bypass_domain');

        if ($bypassDomain !== '') {
            return $tenancy->activateByDomain($bypassDomain);
        }

        return null;
    }

    private function extractListItems(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        $candidates = [
            $payload,
            Arr::get($payload, 'data'),
            Arr::get($payload, 'items'),
            Arr::get($payload, 'content'),
            Arr::get($payload, 'results'),
            Arr::get($payload, 'members'),
            Arr::get($payload, 'data.items'),
            Arr::get($payload, 'data.content'),
            Arr::get($payload, 'data.results'),
            Arr::get($payload, 'data.members'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && array_is_list($candidate)) {
                return $candidate;
            }
        }

        foreach ($payload as $value) {
            if (is_array($value) && array_is_list($value)) {
                return $value;
            }
        }

        return [];
    }

    private function extractMemberDetail(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $candidates = [
            Arr::get($payload, 'data'),
            Arr::get($payload, 'member'),
            Arr::get($payload, 'result'),
            Arr::get($payload, 'data.member'),
            Arr::get($payload, 'data.result'),
            $payload,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !array_is_list($candidate)) {
                return $candidate;
            }
        }

        return [];
    }

    private function upsertFromDetail(Tenant $tenant, array $detail, string $legacyId): array
    {
        $email = $this->normalizeEmail($this->pick($detail, ['email', 'emailAddress', 'email_address']));

        if (!$email) {
            return ['member' => 'skipped'];
        }

        $firstName = $this->toText($this->pick($detail, ['firstname', 'firstName', 'first_name']));
        $lastName = $this->toText($this->pick($detail, ['lastname', 'lastName', 'last_name']));
        $name = trim($firstName . ' ' . $lastName);

        if ($name === '') {
            $name = $this->toText($this->pick($detail, ['name', 'fullName', 'full_name']));
        }

        if ($name === '') {
            $name = Str::before($email, '@');
        }

        if ($firstName === '' && $lastName === '') {
            [$firstName, $lastName] = $this->splitName($name);
        }

        $existingMember = Member::query()
            ->where('email', $email)
            ->first();

        $preferredUsername = trim((string) ($this->pick($detail, ['username', 'userName', 'user_name']) ?? ''));
        $username = $preferredUsername !== '' ? $preferredUsername : Str::before($email, '@');

        $gender = $this->normalizeGender((string) ($this->pick($detail, ['gender']) ?? 'other'));
        $isActive = $this->toBool($this->pick($detail, ['isActive', 'active', 'is_active']), true);

        $planName = $this->extractPlanName($this->pick($detail, ['paymentPlan.planName', 'paymentPlanName', 'paymentPlan', 'payment_plan', 'planName', 'plan']));
        $planPrice = $this->toDecimal($this->pick($detail, ['paymentPlan.price', 'price', 'amount', 'planPrice', 'plan_price']));

        $memberStatus = 'updated';

        DB::transaction(function () use (
            $tenant,
            $email,
            $name,
            $username,
            $detail,
            $legacyId,
            $firstName,
            $lastName,
            $gender,
            $isActive,
            $planName,
            $planPrice,
            &$existingMember,
            &$memberStatus
        ) {
            if (!$existingMember) {
                $existingMember = new Member;
                $existingMember->biometric_member_id = $this->resolveLocalMemberCode($tenant, $detail, $legacyId);
                $existingMember->biometric_last_synced_at = now();
                $memberStatus = 'created';
            }

            $existingMember->first_name = $firstName;
            $existingMember->last_name = $lastName;
            $existingMember->username = $username;
            $existingMember->name = $name;
            $existingMember->gender = $gender;
            $existingMember->email = $email;
            $existingMember->profile_photo_path = $this->toText($this->pick($detail, ['profileImage', 'profile_image', 'avatar']));
            $existingMember->phone_number = $this->toText($this->pick($detail, ['mobile', 'mobileNumber', 'mobile_number', 'phone', 'phoneNumber', 'phone_number']));
            $existingMember->nic = $this->toText($this->pick($detail, ['nicNumber', 'nic_number', 'nic', 'nationalId', 'national_id']));
            $existingMember->date_of_birth = $this->parseDate($this->pick($detail, ['birthDay', 'birthday', 'dateOfBirth', 'date_of_birth', 'dob']));
            $existingMember->address = $this->toText($this->pick($detail, ['address']));
            $existingMember->admission_fee = $this->toDecimal($this->pick($detail, ['entryFee', 'entry_fee', 'admissionFee', 'admission_fee', 'registrationFee', 'registration_fee']));
            $existingMember->payment_plan_id = $planName !== '' ? $this->resolveOrCreatePaymentPlan($tenant, $planName, $planPrice) : null;
            $existingMember->price = $planPrice;
            $existingMember->joined_date = $this->parseDate($this->pick($detail, ['dateOfJoin', 'date_of_join', 'joinedDate', 'joined_date', 'joinDate', 'join_date'])) ?? ($existingMember->joined_date ?: now()->toDateString());
            $existingMember->comment = $this->toText($this->pick($detail, ['remark', 'comment', 'note', 'notes']));
            $existingMember->is_active = $isActive;
            $existingMember->is_verified = true;
            $existingMember->save();
        });

        return [
            'member' => $memberStatus,
            'biometric_member_id' => $existingMember?->biometric_member_id,
        ];
    }

    private function pick(array $source, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $email = strtolower(trim($value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeGender(string $value): string
    {
        $gender = strtolower(trim($value));

        return match ($gender) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => 'other',
        };
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $int = (int) $value;

            return $int > 0 ? $int : null;
        }

        return null;
    }

    private function toDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $candidate = $this->pick($value, ['amount', 'price', 'value', 'planPrice', 'plan_price', 'fee', 'total']);

            if ($candidate !== null) {
                return $this->toDecimal($candidate);
            }

            foreach ($value as $nested) {
                $parsed = $this->toDecimal($nested);

                if ($parsed !== null) {
                    return $parsed;
                }
            }

            return null;
        }

        if (is_object($value)) {
            return $this->toDecimal((array) $value);
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '', (string) $value);

        return preg_match('/-?\d+(?:\.\d+)?/', $normalized, $matches)
            ? (float) $matches[0]
            : null;
    }

    private function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'active'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'inactive'], true)) {
            return false;
        }

        return $default;
    }

    private function toText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return trim((string) $value);
        }

        if (is_object($value)) {
            return $this->toText((array) $value);
        }

        if (is_array($value)) {
            $candidate = $this->pick($value, ['name', 'title', 'label', 'value', 'text', 'description', 'address']);

            if ($candidate !== null) {
                return $this->toText($candidate);
            }

            $parts = [];

            foreach ($value as $item) {
                $text = $this->toText($item);

                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            return trim(implode(' ', $parts));
        }

        return '';
    }

    private function extractPlanName(mixed $value): string
    {
        if (is_array($value)) {
            $candidate = $this->pick($value, ['name', 'planName', 'title', 'label', 'displayName', 'value']);

            if ($candidate !== null) {
                return $this->toText($candidate);
            }
        }

        if (is_object($value)) {
            return $this->extractPlanName((array) $value);
        }

        return $this->toText($value);
    }

    private function resolveLocalMemberCode(Tenant $tenant, array $detail, string $legacyId): string
    {
        $raw = (string) ($this->pick($detail, [
            'memberId',
            'memberid',
            'memberCode',
            'member_code',
            'memberNo',
            'member_no',
            'membershipNo',
            'membership_no',
        ]) ?? '');

        $candidate = trim($raw);

        // Only accept pure-numeric candidates
        if ($candidate !== '' && ctype_digit($candidate)) {
            if (!Member::query()->where('biometric_member_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Use numeric legacyId if available and free within the tenant
        if (ctype_digit($legacyId) && !Member::query()->where('biometric_member_id', $legacyId)->exists()) {
            return $legacyId;
        }

        return Member::generateBiometricMemberId($tenant->id);
    }

    private function resolveOrCreatePaymentPlan(Tenant $tenant, string $name, ?float $price): int
    {
        $plan = PaymentPlan::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($plan) {
            return $plan->id;
        }

        return PaymentPlan::create([
            'name' => $name,
            'duration_value' => 1,
            'duration_unit' => 'month',
            'price' => $price ?? 0,
            'is_active' => true,
        ])->id;
    }
}
