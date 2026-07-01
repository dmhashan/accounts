<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check if user exists in current tenant
        $user = User::where('email', $request->email)
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'We could not find a user with that email address.']);
        }

        $resetLink = $this->passwordResetService->sendResetLink($user, app('tenant'));

        // Only expose reset link in non-production environments
        if (app()->environment('production')) {
            return back()->with('success', 'Password reset link has been sent to your email address.');
        }

        return back()->with('success', 'Password reset link has been sent to your email address.')
            ->with('resetLink', $resetLink);
    }

    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if token exists and is valid
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return back()->withErrors(['email' => 'Invalid or expired password reset token.']);
        }

        // Check if token is not older than 1 hour
        $createdAt = Carbon::parse($resetRecord->created_at);

        if ($createdAt->addHour()->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return back()->withErrors(['email' => 'Password reset token has expired.']);
        }

        // Verify token matches
        if (!Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors(['email' => 'Invalid password reset token.']);
        }

        // Find user and update password
        $user = User::where('email', $request->email)
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login.form')
            ->with('success', 'Your password has been reset successfully. You can now login with your new password.');
    }
}
