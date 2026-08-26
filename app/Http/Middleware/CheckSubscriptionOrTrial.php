<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionOrTrial
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only enforce check for customers
        if ($user && $user->isCustomer()) {
            $accessResult = $this->subscriptionService->checkUserAccessToMeals($user);

            if (!$accessResult['can_access']) {
                return response()->json([
                    'success' => false,
                    'message' => $accessResult['message'],
                    'error_code' => 'SUBSCRIPTION_REQUIRED',
                    'data' => [
                        'can_access' => false,
                        'is_trial' => false,
                        'trial_days_remaining' => 0,
                    ]
                ], 403);
            }
        }

        return $next($request);
    }
}
