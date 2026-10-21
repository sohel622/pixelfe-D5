<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseAuthService
{
    public static function enabled(): bool
    {
        return filled(env('SUPABASE_URL')) && filled(env('SUPABASE_ANON_KEY'));
    }

    public static function baseUrl(): string
    {
        return rtrim((string) env('SUPABASE_URL'), '/');
    }

    public static function authHeaders(bool $serviceRole = false): array
    {
        $key = $serviceRole
            ? (string) env('SUPABASE_SERVICE_ROLE_KEY', env('SUPABASE_ANON_KEY'))
            : (string) env('SUPABASE_ANON_KEY');

        return [
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public static function sendOtp(string $email, bool $createUser = false, array $metadata = []): array
    {
        if (! self::enabled()) {
            return [
                'ok' => false,
                'message' => 'Supabase credentials are not configured yet.',
            ];
        }

        $response = Http::withHeaders(self::authHeaders())
            ->post(self::baseUrl().'/auth/v1/otp', [
                'email' => strtolower(trim($email)),
                'create_user' => $createUser,
                'data' => $metadata,
                'email_redirect_to' => env('SUPABASE_EMAIL_REDIRECT_TO', env('APP_URL')),
            ]);

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => 'Unable to send the Supabase OTP email.',
                'details' => $response->json(),
            ];
        }

        return [
            'ok' => true,
            'message' => 'A one-time sign-in code has been sent to your email address.',
            'payload' => $response->json(),
        ];
    }

    public static function verifyOtp(string $email, string $token): array
    {
        if (! self::enabled()) {
            return [
                'ok' => false,
                'message' => 'Supabase credentials are not configured yet.',
            ];
        }

        $response = Http::withHeaders(self::authHeaders())
            ->post(self::baseUrl().'/auth/v1/verify_otp', [
                'email' => strtolower(trim($email)),
                'token' => trim($token),
                'type' => 'email',
            ]);

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => 'The Supabase OTP verification failed.',
                'details' => $response->json(),
            ];
        }

        return [
            'ok' => true,
            'user' => $response->json('user'),
            'session' => $response->json('session'),
        ];
    }
}
