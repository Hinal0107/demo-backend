<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseTokenVerifierService;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFirebase
{
    protected FirebaseTokenVerifierService $verifier;

    public function __construct(FirebaseTokenVerifierService $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get token from Authorization header
        $authHeader = $request->header('Authorization');
        $token = null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if ($token) {
            // Verify Firebase token
            $payload = $this->verifier->verifyToken($token);

            if ($payload) {
                // Find user by firebase_uid
                $user = User::where('firebase_uid', $payload['uid'])->first();

                if ($user) {
                    if ($user->status === 'BLOCKED') {
                        return response()->json([
                            'success' => false,
                            'message' => 'Your account has been blocked by administrator.',
                        ], 403);
                    }

                    // Authenticate in Laravel
                    auth()->login($user);
                    return $next($request);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Firebase authentication successful, but user is not registered in the system.',
                        'needs_registration' => true,
                        'firebase_uid' => $payload['uid'],
                        'email' => $payload['email'] ?? null,
                        'name' => $payload['name'] ?? null,
                    ], 401);
                }
            }
        }

        // 2. Fallback to standard Sanctum guard (useful for web dashboard, admin, or API tokens)
        if (auth()->guard('sanctum')->check()) {
            $user = auth()->guard('sanctum')->user();
            if ($user->status === 'BLOCKED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been blocked by administrator.',
                ], 403);
            }
            auth()->login($user);
            return $next($request);
        }

        // 3. Fallback to standard web session auth (useful for web admin views)
        if (auth()->check()) {
            if (auth()->user()->status === 'BLOCKED') {
                auth()->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been blocked.',
                ], 403);
            }
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Please provide a valid authorization token.',
        ], 401);
    }
}
