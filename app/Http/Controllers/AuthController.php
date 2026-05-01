<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle login request and return authentication token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            Log::info('Login attempt', ['email' => $validated['email']]);

            // Attempt to authenticate user
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                Log::warning('User not found', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }

            // Check password
            if (!Hash::check($validated['password'], $user->password)) {
                Log::warning('Password mismatch', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'password' => ['The provided password is incorrect.']
                    ]
                ], 401);
            }

            // Generate API token
            $token = Str::random(80);
            $hashedToken = hash('sha256', $token);
            $user->update(['api_token' => $hashedToken]);

            Log::info('Login successful', ['email' => $validated['email'], 'user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user->toArray(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle logout request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        // Revoke token
        $user->update(['api_token' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ], 200);
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ], 200);
    }

    /**
     * Get authenticated user from Bearer token
     *
     * @param Request $request
     * @return User|null
     */
    private function getAuthenticatedUser(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        // Hash the token to compare with stored hash
        $hashedToken = hash('sha256', $token);

        // Find user by hashed token
        return User::where('api_token', $hashedToken)->first();
    }
}
