<?php

namespace App\Services;

use App\Mail\PasswordResetLinkMail;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantEmailBranding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(
        private readonly TenantMailService $tenantMail,
    ) {}

    public function sendResetLink(User $user, Tenant $tenant): string
    {
        $resetUrl = $this->createResetUrl($user);

        $this->tenantMail->mailerForTenant($tenant->id)
            ->to($user->email, $user->name)
            ->send(new PasswordResetLinkMail(
                $tenant->name,
                $resetUrl,
                TenantEmailBranding::forTenant($tenant),
                $user->name,
            ));

        return $resetUrl;
    }

    private function createResetUrl(User $user): string
    {
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        return route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
