<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class FirebaseTokenVerifierService
{
    protected ?string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id') ?: env('FIREBASE_PROJECT_ID', 'tiffin-service-mock');
    }

    /**
     * Verify Firebase ID token and return decoded payload.
     * Supports mock tokens for testing.
     *
     * @param string $token
     * @return array|null
     */
    public function verifyToken(string $token): ?array
    {
        if (str_starts_with($token, 'mock-uid-')) {
            $role = 'CUSTOMER';
            $lowerToken = strtolower($token);
            if (str_contains($lowerToken, 'admin')) {
                $role = 'SUPER_ADMIN';
            } elseif (str_contains($lowerToken, 'restaurant')) {
                $role = 'RESTAURANT';
            }

            return [
                'uid' => $token,
                'email' => strtolower($role) . '@tiffin.com',
                'name' => 'Mock ' . ucfirst(strtolower($role)),
                'role' => $role,
                'is_mock' => true,
            ];
        }

        try {
            $keys = $this->getGooglePublicKeys();
            if (empty($keys)) {
                Log::error('Firebase Token Verification: Could not fetch Google public keys.');
                return null;
            }

            // Decode the JWT token
            $decoded = JWT::decode($token, $keys);

            // Validate claims
            $now = time();
            if (!isset($decoded->exp) || $decoded->exp < $now) {
                Log::warning('Firebase Token Verification: Token expired.');
                return null;
            }

            if (!isset($decoded->iss) || $decoded->iss !== "https://securetoken.google.com/{$this->projectId}") {
                Log::warning('Firebase Token Verification: Invalid issuer.', ['iss' => $decoded->iss ?? null]);
                return null;
            }

            if (!isset($decoded->aud) || $decoded->aud !== $this->projectId) {
                Log::warning('Firebase Token Verification: Invalid audience.', ['aud' => $decoded->aud ?? null]);
                return null;
            }

            return [
                'uid' => $decoded->sub,
                'email' => $decoded->email ?? null,
                'name' => $decoded->name ?? null,
                'phone' => $decoded->phone_number ?? null,
                'picture' => $decoded->picture ?? null,
                'is_mock' => false,
            ];
        } catch (Exception $e) {
            Log::error('Firebase Token Verification Error: ' . $e->getMessage(), [
                'token' => substr($token, 0, 15) . '...',
            ]);
            return null;
        }
    }

    /**
     * Fetch Google's public certificates and convert them into JWT Key structures.
     * Caches keys for 24 hours.
     *
     * @return array
     */
    protected function getGooglePublicKeys(): array
    {
        return Cache::remember('google_public_keys', 86400, function () {
            try {
                $response = Http::get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
                if ($response->successful()) {
                    $certs = $response->json();
                    $keys = [];
                    foreach ($certs as $kid => $cert) {
                        $keys[$kid] = new Key($cert, 'RS256');
                    }
                    return $keys;
                }
            } catch (Exception $e) {
                Log::error('Failed to retrieve Google public keys: ' . $e->getMessage());
            }
            return [];
        });
    }
}
