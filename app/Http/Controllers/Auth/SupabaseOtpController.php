<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupabaseOtpController extends Controller
{
    public function request(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if (! SupabaseAuthService::enabled()) {
            return redirect()->back()->withErrors([
                'email' => 'Supabase Auth credentials are not configured.',
            ]);
        }

        $createUser = (bool) $request->boolean('create_user', true);
        $result = SupabaseAuthService::sendOtp($request->input('email'), $createUser, [
            'source' => 'pixelfed',
        ]);

        if (! $result['ok']) {
            return redirect()->back()->withErrors([
                'email' => $result['message'],
            ]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        if (! SupabaseAuthService::enabled()) {
            return redirect()->back()->withErrors([
                'token' => 'Supabase Auth credentials are not configured.',
            ]);
        }

        $result = SupabaseAuthService::verifyOtp($request->input('email'), (string) $request->input('token'));

        if (! $result['ok']) {
            return redirect()->back()->withErrors([
                'token' => $result['message'],
            ]);
        }

        return redirect()->route('login')->with('status', 'Supabase OTP verified successfully. You can now sign in.');
    }
}
