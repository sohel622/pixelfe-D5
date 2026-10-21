<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseRealtimeService
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

    public static function subscribe(string $channel, string $table, array $filter = []): array
    {
        return [
            'ok' => self::enabled(),
            'channel' => $channel,
            'table' => $table,
            'filter' => $filter,
            'endpoint' => self::baseUrl().'/realtime/v1/websocket',
            'message' => 'Realtime listeners should be registered with Supabase Realtime using the channel and table filters for notifications.',
        ];
    }

    public static function saveNotification(string $userId, array $data): array
    {
        if (! self::enabled() || blank($userId)) {
            return [
                'ok' => false,
                'message' => 'Supabase Realtime is not configured for notification persistence.',
            ];
        }

        $payload = array_merge([
            'user_id' => $userId,
            'read' => false,
            'created_at' => now()->toIso8601String(),
        ], $data);

        $response = Http::withHeaders(self::authHeaders(true))
            ->post(self::baseUrl().'/rest/v1/notifications', [$payload]);

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => 'Unable to save the notification to Supabase.',
                'details' => $response->json(),
            ];
        }

        return [
            'ok' => true,
            'payload' => $response->json(),
        ];
    }
}
