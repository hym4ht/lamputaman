<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    protected ?string $projectId = null;
    protected ?string $clientEmail = null;
    protected ?string $privateKey = null;

    public function __construct()
    {
        // Try loading from JSON file first
        $credentialsPath = env('FIREBASE_CREDENTIALS');
        if ($credentialsPath && file_exists(base_path($credentialsPath))) {
            $config = json_decode(file_get_contents(base_path($credentialsPath)), true);
            $this->projectId = $config['project_id'] ?? null;
            $this->clientEmail = $config['client_email'] ?? null;
            $this->privateKey = $config['private_key'] ?? null;
        } else {
            // Fallback to direct environment variables
            $this->projectId = env('FIREBASE_PROJECT_ID');
            $this->clientEmail = env('FIREBASE_CLIENT_EMAIL');
            $this->privateKey = env('FIREBASE_PRIVATE_KEY') ? str_replace('\n', "\n", env('FIREBASE_PRIVATE_KEY')) : null;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->projectId) && !empty($this->clientEmail) && !empty($this->privateKey);
    }

    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        // Cache token for 55 minutes
        return Cache::remember('firebase_fcm_access_token', 3300, function () {
            $jwt = $this->generateJwt();
            if (!$jwt) {
                return null;
            }

            try {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('FCM OAuth Token Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error('FCM OAuth Token Exception', ['message' => $e->getMessage()]);
            }

            return null;
        });
    }

    protected function generateJwt(): ?string
    {
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        
        $now = time();
        $payload = json_encode([
            'iss' => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = '';
        $success = openssl_sign(
            $base64UrlHeader . "." . $base64UrlPayload,
            $signature,
            $this->privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            Log::error('FCM JWT signature creation failed. Check private key format.');
            return null;
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    protected function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    public function sendNotificationToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('Firebase FCM notification not sent: Service not configured or failed to retrieve access token.');
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'topic' => $topic,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data),
                        'webpush' => [
                            'notification' => [
                                'icon' => '/logo.png',
                                'badge' => '/logo.png',
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                Log::info("FCM Notification sent successfully to topic '{$topic}'", ['title' => $title]);
                return true;
            }

            Log::error('FCM Send Notification Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('FCM Send Notification Exception', ['message' => $e->getMessage()]);
        }

        return false;
    }

    public function sendNotificationToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data),
                        'webpush' => [
                            'notification' => [
                                'icon' => '/logo.png',
                                'badge' => '/logo.png',
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return true;
            }

            // If token is invalid, we can remove it (handled by caller)
            if ($response->status() === 400 || $response->status() === 404) {
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM Send to Token Exception', ['message' => $e->getMessage()]);
        }

        return false;
    }

    public function broadcast(string $title, string $body, array $data = []): void
    {
        // 1. Send to topic
        $this->sendNotificationToTopic('garden_notifications', $title, $body, $data);

        // 2. Send to all registered web client tokens
        $tokens = Cache::get('fcm_tokens', []);
        $activeTokens = [];

        foreach ($tokens as $token) {
            $success = $this->sendNotificationToToken($token, $title, $body, $data);
            if ($success) {
                $activeTokens[] = $token;
            }
        }

        // Clean up invalid/expired tokens
        if (count($activeTokens) !== count($tokens)) {
            Cache::put('fcm_tokens', $activeTokens, 86400 * 30);
        }
    }
}
