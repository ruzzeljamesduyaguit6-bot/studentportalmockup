<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            // Check if user is already authenticated via session
            if (!auth()->guard()->check()) {
                return redirect('/login');
            }
        } else {
            // Validate Bearer token
            $hashedToken = hash('sha256', $token);
            $user = User::where('api_token', $hashedToken)->first();

            if (!$user) {
                return redirect('/login');
            }

            // Authenticate the user
            auth()->guard() -> setUser($user);
        }

        return $next($request);
    }
}

