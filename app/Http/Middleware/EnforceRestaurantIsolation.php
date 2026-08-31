<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRestaurantIsolation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Super Admin has global bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Restaurant staff/manager must belong to a restaurant
        if ($user->isRestaurant()) {
            $restaurantUser = $user->restaurantUsers()->first();

            if (!$restaurantUser || !$restaurantUser->restaurant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. This user account is not linked to any active restaurant.',
                ], 403);
            }

            $userRestaurantId = $restaurantUser->restaurant_id;

            // Enforce scope: Bind the resolved restaurant_id to the request
            $request->attributes->set('restaurant_id', $userRestaurantId);

            // If the route has a restaurantId parameter, verify it matches
            $routeRestaurantId = $request->route('restaurantId');
            if ($routeRestaurantId && (int)$routeRestaurantId !== (int)$userRestaurantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have permissions to access this restaurant\'s data.',
                ], 403);
            }
        }

        return $next($request);
    }
}
