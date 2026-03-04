<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);
        $this->setDynamicRedirectUri($request, $provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);
        $this->setDynamicRedirectUri($request, $provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            return redirect()->route('login.form')->with('error', 'Unable to sign in with ' . ucfirst($provider) . '. Please try again.');
        }

        $tenant = app('tenant');
        $email = $socialUser->getEmail();

        if (!$email) {
            return redirect()->route('login.form')
                ->with('error', 'SSO login failed: provider did not return an email address.');
        }

        $user = User::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if (!$user) {
            return redirect()->route('login.form')
                ->with('error', 'No account found for this SSO email in this tenant.');
        }

        $updates = [
            'social_provider' => $provider,
            'social_provider_id' => $socialUser->getId(),
        ];

        if ($socialUser->getAvatar()) {
            $updates['avatar'] = $socialUser->getAvatar();
        }

        $user->update($updates);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function ensureSupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['google', 'apple'], true), 404);
    }

    private function setDynamicRedirectUri(Request $request, string $provider): void
    {
        if ($provider !== 'google') {
            return;
        }

        config()->set(
            'services.' . $provider . '.redirect',
            $this->resolveCallbackUrl($request, $provider)
        );
    }

    private function resolveCallbackUrl(Request $request, string $provider): string
    {
        return $request->getSchemeAndHttpHost() . route('auth.social.callback', ['provider' => $provider], false);
    }
}
