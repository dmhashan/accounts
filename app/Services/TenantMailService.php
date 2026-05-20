<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;

class TenantMailService
{
    public function __construct(
        private readonly TenantConfigurationService $tenantConfig,
    ) {}

    /**
     * Return a mailer configured with the tenant's SMTP settings.
     * Falls back to the default application mailer when no tenant SMTP is set.
     */
    public function mailerForTenant(int $tenantId): Mailer
    {
        $cfg = $this->tenantConfig->all($tenantId);

        $host = ($cfg['notifications.email.smtp_host'] ?? '') ?: null;
        $username = ($cfg['notifications.email.smtp_username'] ?? '') ?: null;
        $password = ($cfg['notifications.email.smtp_password'] ?? '') ?: null;
        $port = (int) (($cfg['notifications.email.smtp_port'] ?? '') ?: 587);
        $encryption = ($cfg['notifications.email.smtp_encryption'] ?? '') ?: 'tls';
        $fromAddress = ($cfg['notifications.email.from_address'] ?? '') ?: null;
        $fromName = ($cfg['notifications.email.from_name'] ?? '') ?: config('mail.from.name');

        // Fall back to default mailer when tenant SMTP is not configured
        if (!$host || !$username) {
            return app('mailer');
        }

        $mailerKey = 'tenant_smtp_' . $tenantId;

        config(["mail.mailers.{$mailerKey}" => [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption === 'none' ? null : $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => 30,
        ]]);

        $mailer = Mail::mailer($mailerKey);

        if ($fromAddress) {
            $mailer->alwaysFrom($fromAddress, $fromName);
        }

        return $mailer;
    }
}
