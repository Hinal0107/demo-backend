<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRestaurant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isRestaurant()) {
            Auth::logout();
            return redirect()->route('restaurant.login')->withErrors([
                'email' => 'Please sign in with a restaurant manager account.'
            ]);
        }

        $restaurant = Auth::user()->restaurant;
        if (!$restaurant) {
            Auth::logout();
            return redirect()->route('restaurant.login')->withErrors([
                'email' => 'Your account is not linked to any active restaurant.'
            ]);
        }

        return $next($request);
    }
}
