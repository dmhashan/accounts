<?php

namespace App\Services;

use App\Enums\DateFormat;
use App\Enums\TimeFormat;
use App\Models\TenantConfiguration;
use Illuminate\Support\Str;

class TenantConfigurationService
{
    public const BODY_MEASUREMENT_FIELDS_KEY = 'body_measurements.fields';

    private const BODY_MEASUREMENT_FIELDS_DEFAULT_JSON = '[{"key":"chest","label":"Chest","enabled":true,"sort_order":10,"built_in":true},{"key":"right_arm","label":"Right Arm","enabled":true,"sort_order":20,"built_in":true},{"key":"left_arm","label":"Left Arm","enabled":true,"sort_order":30,"built_in":true},{"key":"two_above_navel","label":"2\" Above Navel","enabled":true,"sort_order":40,"built_in":true},{"key":"stomach_at_navel","label":"Stomach at Navel","enabled":true,"sort_order":50,"built_in":true},{"key":"two_below_navel","label":"2\" Below Navel","enabled":true,"sort_order":60,"built_in":true},{"key":"hips_widest_point","label":"Hips - Widest Point","enabled":true,"sort_order":70,"built_in":true},{"key":"right_leg","label":"Right Leg","enabled":true,"sort_order":80,"built_in":true},{"key":"left_leg","label":"Left Leg","enabled":true,"sort_order":90,"built_in":true}]';

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
        'general.color_theme' => ['Color Theme', 'crimson'],
        'general.color_mode' => ['Default Color Mode', 'system'],
        'general.member_notifications' => ['Member Notification Rules', '{}'],
        'general.gowa_enabled' => ['GoWA Integration Enabled', '0'],
        'general.gowa_url' => ['GoWA Server URL', ''],
        'general.gowa_api_key' => ['GoWA API Key', ''],
        'general.gowa_session_id' => ['GoWA Device ID / Session ID', ''],
        'general.gowa_groups' => ['GoWA Rule-Based Groups', '[]'],
        self::BODY_MEASUREMENT_FIELDS_KEY => ['Body Measurement Fields', self::BODY_MEASUREMENT_FIELDS_DEFAULT_JSON],

        // Biometric device integration
        'biometric.enabled' => ['Biometric Integration',        '0'],
        'biometric.device_maker' => ['Device Maker',                 ''],
        'biometric.device_model' => ['Device Model',                 ''],
        'biometric.device_ip' => ['Device IP / Hostname',         ''],
        'biometric.device_port' => ['Device Port',                  '80'],
        'biometric.device_username' => ['Device Username',              'admin'],
        'biometric.device_password' => ['Device Password',              ''],
        'biometric.sync_members' => ['Sync Members to Device',       '0'],
        'biometric.access_control' => ['Enforce Access by Payment',    '0'],
        'biometric.grace_period_days' => ['Access Grace Period (days)',   '0'],
        'biometric.webhook_enabled' => ['Real-Time Event Push',          '0'],
        'biometric.webhook_server_host' => ['Webhook Server Host',           ''],
        'biometric.webhook_server_port' => ['Webhook Server Port',           '80'],
        'biometric.webhook_token' => ['Webhook Token',                  ''],
        'biometric.access_events_sync_from' => ['Access Events Sync From',       ''],

        // Member ID & Biometric ID format preferences
        'member.id_prefix' => ['Member ID Prefix', ''],
        'member.id_next_number' => ['Member ID Next Number', '1'],
        'member.id_padding' => ['Member ID Zero Padding', '4'],
        'member.id_auto_generate' => ['Auto Generate Member ID', '1'],
        'biometric.id_prefix' => ['Biometric ID Prefix', ''],
        'biometric.id_next_number' => ['Biometric ID Next Number', '1'],
        'biometric.id_padding' => ['Biometric ID Zero Padding', '4'],
        'biometric.id_same_as_member_id' => ['Biometric ID Same as Member ID', '1'],
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
        ];

        return array_values(array_filter($requested, function (string $channel) use ($cfg, $map): bool {
            if ($channel === 'sms') {
                return ($cfg['notifications.sms.enabled'] ?? '0') === '1';
            }

            // If the key isn't in the map (unknown channel), allow it through
            if (!isset($map[$channel])) {
                return true;
            }

            return ($cfg[$map[$channel]] ?? '0') === '1';
        }));
    }

    public function all(int $tenantId): array
    {
        $rows = TenantConfiguration::query()
            ->pluck('value', 'key')
            ->all();

        $result = [];

        foreach (self::SCHEMA as $key => [$title, $default]) {
            $value = $rows[$key] ?? $default;

            if ($key === self::BODY_MEASUREMENT_FIELDS_KEY) {
                $value = $this->bodyMeasurementFieldsJson($value);
            }

            $result[$key] = $value;
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

            if ($key === self::BODY_MEASUREMENT_FIELDS_KEY) {
                $value = $this->bodyMeasurementFieldsJson($value);
            }

            TenantConfiguration::updateOrCreate(
                ['key' => $key],
                ['title' => $title, 'value' => (string) $value],
            );
        }

        return $this->all($tenantId);
    }

    /**
     * @return array<int, array{key: string, label: string, enabled: bool, sort_order: int, built_in: bool}>
     */
    public function bodyMeasurementFields(int $tenantId, bool $enabledOnly = false): array
    {
        $fields = $this->normalizeBodyMeasurementFields(
            $this->all($tenantId)[self::BODY_MEASUREMENT_FIELDS_KEY] ?? self::BODY_MEASUREMENT_FIELDS_DEFAULT_JSON,
        );

        if ($enabledOnly) {
            $fields = array_values(array_filter($fields, fn (array $field): bool => $field['enabled']));
        }

        return $fields;
    }

    public function bodyMeasurementFieldsJson(mixed $value): string
    {
        $json = json_encode($this->normalizeBodyMeasurementFields($value), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        return is_string($json) ? $json : self::BODY_MEASUREMENT_FIELDS_DEFAULT_JSON;
    }

    /**
     * @return array<int, array{key: string, label: string, enabled: bool, sort_order: int, built_in: bool}>
     */
    public function normalizeBodyMeasurementFields(mixed $value): array
    {
        $configured = $this->decodeMeasurementFields($value);
        $defaults = $this->decodeMeasurementFields(self::BODY_MEASUREMENT_FIELDS_DEFAULT_JSON);
        $fieldsByKey = [];

        foreach ($defaults as $field) {
            $fieldsByKey[$field['key']] = $field;
        }

        foreach ($configured as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = $this->normalizeMeasurementFieldKey((string) ($field['key'] ?? $field['label'] ?? ''));

            if ($key === '' || in_array($key, ['weight', 'height', 'measurement_date'], true)) {
                continue;
            }

            $existing = $fieldsByKey[$key] ?? null;
            $label = trim((string) ($field['label'] ?? ''));
            $enabled = filter_var($field['enabled'] ?? ($existing['enabled'] ?? true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            $fieldsByKey[$key] = [
                'key' => $key,
                'label' => $label !== '' ? mb_substr($label, 0, 100) : ($existing['label'] ?? Str::headline(str_replace('_', ' ', $key))),
                'enabled' => $enabled ?? (bool) ($existing['enabled'] ?? true),
                'sort_order' => max(0, min(999, (int) ($field['sort_order'] ?? $existing['sort_order'] ?? (($index + 1) * 10)))),
                'built_in' => (bool) ($existing['built_in'] ?? false),
            ];
        }

        $fields = array_values($fieldsByKey);

        usort($fields, fn (array $a, array $b): int => [$a['sort_order'], $a['label'], $a['key']] <=> [$b['sort_order'], $b['label'], $b['key']]);

        return $fields;
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeMeasurementFields(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private function normalizeMeasurementFieldKey(string $value): string
    {
        return mb_substr(Str::slug($value, '_'), 0, 64);
    }
}
