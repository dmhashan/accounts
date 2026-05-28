<?php

namespace App\Services;

use App\Enums\DateFormat;
use App\Enums\TimeFormat;
use App\Models\TenantConfiguration;

class TenantConfigurationService
{
    /**
     * Configuration key metadata: key => [title, default]
     */
    private const SCHEMA = [
        'notifications.inapp.enabled' => ['In-App Notifications',        '0'],
        'notifications.email.enabled' => ['Email Notifications',          '0'],
        'notifications.email.smtp_host' => ['SMTP Host',                    ''],
        'notifications.email.smtp_port' => ['SMTP Port',                    '587'],
        'notifications.email.smtp_username' => ['SMTP Username',                ''],
        'notifications.email.smtp_password' => ['SMTP Password',                ''],
        'notifications.email.smtp_encryption' => ['SMTP Encryption',              'tls'],
        'notifications.email.from_address' => ['From Email Address',           ''],
        'notifications.email.from_name' => ['From Name',                    ''],
        'notifications.sms.enabled' => ['SMS Notifications',            '0'],
        'notifications.sms.user_id' => ['SMS User ID',                  ''],
        'notifications.sms.api_key' => ['SMS API Key',                  ''],
        'notifications.sms.sender_id' => ['SMS Sender ID',                ''],

        // General display preferences
        'general.date_format' => ['Date Format', DateFormat::DayMonYear->value],
        'general.time_format' => ['Time Format', TimeFormat::H24->value],

        // Biometric device integration
        'biometric.enabled' => ['Biometric Integration',        '0'],
        'biometric.device_maker' => ['Device Maker',                 ''],
        'biometric.device_model' => ['Device Model',                 ''],
        'biometric.device_ip' => ['Device IP / Hostname',         ''],
        'biometric.device_port' => ['Device Port',                  '80'],
        'biometric.device_username' => ['Device Username',              'admin'],
        'biometric.device_password' => ['Device Password',              ''],
        'biometric.sync_members' => ['Sync Members to Device',       '0'],
        'biometric.sync_attendance' => ['Sync Attendance from Device',  '0'],
        'biometric.access_control' => ['Enforce Access by Payment',    '0'],
        'biometric.grace_period_days' => ['Access Grace Period (days)',   '0'],
    ];

    /**
     * Return the subset of the given channels that are enabled in tenant config.
     * Channel names: 'sms', 'email', 'in_app'
     *
     * @param  string[]  $requested
     * @return string[]
     */
    public function enabledChannels(int $tenantId, array $requested): array
    {
        $cfg = $this->all($tenantId);

        $map = [
            'in_app' => 'notifications.inapp.enabled',
            'email' => 'notifications.email.enabled',
            'sms' => 'notifications.sms.enabled',
        ];

        return array_values(array_filter($requested, function (string $channel) use ($cfg, $map): bool {
            // If the key isn't in the map (unknown channel), allow it through
            if (!isset($map[$channel])) {
                return true;
            }

            return ($cfg[$map[$channel]] ?? '0') === '1';
        }));
    }

    public function all(int $tenantId): array
    {
        $rows = TenantConfiguration::where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->all();

        $result = [];

        foreach (self::SCHEMA as $key => [$title, $default]) {
            $result[$key] = $rows[$key] ?? $default;
        }

        return $result;
    }

    public function updateBatch(int $tenantId, array $data): array
    {
        $allowed = array_keys(self::SCHEMA);

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            [$title] = self::SCHEMA[$key];

            TenantConfiguration::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['title' => $title, 'value' => (string) $value],
            );
        }

        return $this->all($tenantId);
    }
}
