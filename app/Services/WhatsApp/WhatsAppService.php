<?php

namespace App\Services\WhatsApp;

use App\Services\TenantConfigurationService;
use App\Services\WhatsApp\Drivers\GoWaDriver;

class WhatsAppService
{
    /**
     * Cache of instantiated drivers.
     *
     * @var array<string, WhatsAppClientInterface>
     */
    private array $drivers = [];

    public function __construct(
        private readonly TenantConfigurationService $configService,
    ) {}

    /**
     * Get or resolve a WhatsApp client driver instance.
     */
    public function driver(?string $name = null): WhatsAppClientInterface
    {
        $name = $name ?: 'gowa';

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->createDriver($name);
    }

    /**
     * Factory method for creating driver instances.
     */
    protected function createDriver(string $name): WhatsAppClientInterface
    {
        return match ($name) {
            'gowa' => new GoWaDriver,
            default => throw new \InvalidArgumentException("Unsupported WhatsApp driver: [{$name}]"),
        };
    }

    /**
     * Register a custom driver implementation.
     */
    public function extend(string $name, WhatsAppClientInterface $driver): self
    {
        $this->drivers[$name] = $driver;

        return $this;
    }

    /**
     * Helper to read / get messages for a given phone number.
     *
     * Usage: $whatsAppService->read($number, $limit)
     */
    public function read(string $number, int $limit = 50, array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->getMessages($number, $limit, $config);
    }

    /**
     * Helper to send a text message.
     *
     * Usage: $whatsAppService->send($number, $message)
     */
    public function send(string $number, string $message, array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->sendMessage($number, $message, $config);
    }

    /**
     * Helper to send media (image/file/document/audio).
     *
     * Usage: $whatsAppService->sendMedia($number, $mediaUrl, $caption, $mediaType)
     */
    public function sendMedia(string $number, string $mediaUrl, string $caption = '', string $mediaType = 'image', array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->sendMedia($number, $mediaUrl, $caption, $mediaType, $config);
    }

    /**
     * Helper to test connection to the WhatsApp provider.
     */
    public function testConnection(?array $config = null): array
    {
        $resolved = $this->resolveConfig($config ?? []);

        return $this->driver($resolved['driver'] ?? 'gowa')->testConnection($resolved);
    }

    /**
     * Helper to get WhatsApp device status.
     */
    public function getDeviceStatus(?array $config = null): array
    {
        $resolved = $this->resolveConfig($config ?? []);

        return $this->driver($resolved['driver'] ?? 'gowa')->getDeviceStatus($resolved);
    }

    /**
     * Helper to check if a phone number is registered on WhatsApp.
     */
    public function checkUser(string $number, array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->checkUser($number, $config);
    }

    /**
     * Helper to get WhatsApp user avatar.
     */
    public function getUserAvatar(string $number, array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->getUserAvatar($number, $config);
    }

    /**
     * Helper to get WhatsApp user info.
     */
    public function getUserInfo(string $number, array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->getUserInfo($number, $config);
    }

    /**
     * Helper to get login QR code for device pairing.
     */
    public function getLoginQr(?array $config = null): array
    {
        $resolved = $this->resolveConfig($config ?? []);

        return $this->driver($resolved['driver'] ?? 'gowa')->getLoginQr($resolved);
    }

    /**
     * Helper to mark a message as read.
     */
    public function markAsRead(string $messageId, string $number, array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->markAsRead($messageId, $number, $config);
    }

    /**
     * Helper to send typing / chat presence.
     */
    public function sendChatPresence(string $number, string $action = 'start', array $options = []): array
    {
        $config = $this->resolveConfig($options);

        return $this->driver($config['driver'] ?? 'gowa')->sendChatPresence($number, $action, $config);
    }

    /**
     * Retrieve WhatsApp configuration for active or given tenant.
     */
    public function getConfig(?int $tenantId = null): array
    {
        if ($tenantId === null && app()->bound('tenant') && app('tenant')) {
            $tenantId = (int) app('tenant')->id;
        }

        $all = $tenantId !== null ? $this->configService->all($tenantId) : [];

        $enabled = ($all['general.gowa_enabled'] ?? '0') === '1';
        $url = (string) ($all['general.gowa_url'] ?? '');
        $apiKey = (string) ($all['general.gowa_api_key'] ?? '');
        $sessionId = (string) ($all['general.gowa_session_id'] ?? '');

        return [
            'enabled' => $enabled,
            'url' => $url,
            'api_key' => $apiKey,
            'session_id' => $sessionId,
            'driver' => 'gowa',
        ];
    }

    /**
     * Check if WhatsApp is enabled in tenant settings.
     */
    public function isEnabled(?int $tenantId = null): bool
    {
        $config = $this->getConfig($tenantId);

        return $config['enabled'] && !empty($config['url']);
    }

    /**
     * Alias for isEnabled.
     */
    public function isWhatsAppEnabled(?int $tenantId = null): bool
    {
        return $this->isEnabled($tenantId);
    }

    /**
     * Merge options with tenant config if options don't override.
     */
    private function resolveConfig(array $options = []): array
    {
        $tenantConfig = $this->getConfig();

        return array_merge($options, [
            'enabled' => $options['enabled'] ?? $tenantConfig['enabled'],
            'url' => !empty($options['url']) ? $options['url'] : $tenantConfig['url'],
            'api_key' => array_key_exists('api_key', $options) ? $options['api_key'] : $tenantConfig['api_key'],
            'session_id' => array_key_exists('session_id', $options) ? $options['session_id'] : $tenantConfig['session_id'],
            'driver' => $options['driver'] ?? $tenantConfig['driver'] ?? 'gowa',
        ]);
    }
}
