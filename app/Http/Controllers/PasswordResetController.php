<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // Show the "forgot password" form
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    // Send a reset link to the student's email
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user  = Applicant::where('email', $email)->first();

        // Always return the same message — prevents email enumeration
        if (!$user) {
            return back()->with('status', 'If that email exists in our system, a reset link has been sent.');
        }

        // Invalidate any existing tokens for this user
        DB::table('password_reset_tokens')
            ->where('user_id', $user->applicant_id)
            ->where('user_type', 'applicant')
            ->update(['used' => true]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'user_id'    => $user->applicant_id,
            'user_type'  => 'applicant',
            'token'      => hash('sha256', $token),
            'created_at' => now(),
            'expires_at' => now()->addHour(),
            'used'       => false,
        ]);

        $resetUrl = url('/reset-password.php?token=' . $token . '&email=' . urlencode($email));

        // Send email — falls back to log driver if SMTP not configured
        Mail::send('emails.password-reset', ['resetUrl' => $resetUrl, 'user' => $user], function ($m) use ($user) {
            $m->to($user->email, $user->fullName)
              ->subject('Reset Your VRS Password');
        });

        return back()->with('status', 'If that email exists in our system, a reset link has been sent.');
    }

    // Show the "enter new password" form
    public function showResetForm(Request $request): View|RedirectResponse
    {
        $token = $request->query('token', '');
        $email = $request->query('email', '');

        if (!$token || !$email) {
            return redirect()->route('auth.login')->with('error', 'Invalid reset link.');
        }

        return view('auth.reset-password', compact('token', 'email'));
    }

    // Apply the new password
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:12|confirmed',
            'password_confirmation' => 'required',
        ]);

        $email        = strtolower(trim($request->input('email')));
        $tokenPlain   = $request->input('token');
        $tokenHashed  = hash('sha256', $tokenPlain);

        $user = Applicant::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Invalid reset link.']);
        }

        $record = DB::table('password_reset_tokens')
            ->where('user_id', $user->applicant_id)
            ->where('user_type', 'applicant')
            ->where('token', $tokenHashed)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$record) {
            return back()->withErrors(['token' => 'This reset link is invalid or has expired. Please request a new one.']);
        }

        // Mark token as used and update password
        DB::table('password_reset_tokens')
            ->where('user_id', $user->applicant_id)
            ->update(['used' => true]);

        $user->update(['password' => Hash::make($request->input('password'))]);

        return redirect()->route('auth.login')
            ->with('success', 'Password reset successfully. You can now log in with your new password.');
    }
}
