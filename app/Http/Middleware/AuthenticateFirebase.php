<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseTokenVerifierService;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
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
     * Supports Sanctum tokens, Firebase JWT tokens, mock tokens, and direct firebase_uid lookup.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract token from Authorization header, server vars, custom headers, or query params
        $authHeader = $request->header('Authorization')
            ?? $request->server('HTTP_AUTHORIZATION')
            ?? $request->server('REDIRECT_HTTP_AUTHORIZATION')
            ?? $request->server('REDIRECT_REDIRECT_HTTP_AUTHORIZATION')
            ?? $request->header('X-Authorization')
            ?? $request->header('x-token')
            ?? $request->query('token')
            ?? $request->query('bearer_token');

        $token = null;

        if ($authHeader) {
            if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
                $token = trim($matches[1]);
            } else {
                $token = trim($authHeader);
            }
        }

        if ($token) {
            // A. Check standard Sanctum guard first
            if (auth()->guard('sanctum')->check()) {
                $user = auth()->guard('sanctum')->user();
                if ($user) {
                    return $this->authenticateUser($request, $user, $next);
                }
            }

            // B. Try Sanctum findToken directly (handles plain text or formatted tokens)
            $pat = PersonalAccessToken::findToken($token);
            if ($pat && $pat->tokenable) {
                $pat->update(['last_used_at' => now()]);
                return $this->authenticateUser($request, $pat->tokenable, $next);
            }

            // C. Verify Firebase token (JWT or mock token)
            $payload = $this->verifier->verifyToken($token);
            if ($payload) {
                $user = User::where('firebase_uid', $payload['uid'])->first();
                if ($user) {
                    return $this->authenticateUser($request, $user, $next);
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

            // D. Direct firebase_uid lookup fallback (for direct UID auth tokens)
            $userByUid = User::where('firebase_uid', $token)->first();
            if ($userByUid) {
                return $this->authenticateUser($request, $userByUid, $next);
            }
        }

        // 2. Fallback to standard web session auth (useful for web admin views)
        if (auth()->check() && auth()->user()) {
            return $this->authenticateUser($request, auth()->user(), $next);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Please provide a valid authorization token.',
        ], 401);
    }

    /**
     * Authenticate resolved user into request and auth context.
     */
    private function authenticateUser(Request $request, User $user, Closure $next): Response
    {
        if ($user->status === 'BLOCKED') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been blocked by administrator.',
            ], 403);
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
