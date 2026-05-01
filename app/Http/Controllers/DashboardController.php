<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get dashboard data based on user role
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Hash the token to find user
        $hashedToken = hash('sha256', $token);
        $user = \App\Models\User::where('api_token', $hashedToken)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        $isAdmin = $user->user_type === 'admin';

        // Return dashboard data based on role
        return response()->json([
            'success' => true,
            'user' => $user,
            'isAdmin' => $isAdmin,
            'config' => [
                'navbarClass' => $isAdmin ? 'admin-navbar' : '',
                'navbarIcon' => $isAdmin ? '🔐' : '',
                'navbarTitle' => $isAdmin ? 'Admin Dashboard' : 'User Dashboard',
                'welcomeTitle' => $isAdmin ? 'Admin Dashboard' : 'Welcome to your Dashboard',
                'welcomeText' => $isAdmin 
                    ? 'Welcome to the administrative dashboard. You have full access to system settings and user management.'
                    : 'You are successfully logged in to the Role Based System. Here you can manage your profile and view your personal settings.',
                'showStats' => $isAdmin,
                'stats' => $isAdmin ? [
                    ['label' => 'Total Users', 'value' => '7'],
                    ['label' => 'Admins', 'value' => '1'],
                    ['label' => 'Regular Users', 'value' => '6']
                ] : []
            ],
            'actions' => $isAdmin ? $this->getAdminActions() : $this->getUserActions()
        ], 200);
    }

    /**
     * Get admin-specific actions
     *
     * @return array
     */
    private function getAdminActions()
    {
        return [
            ['title' => 'User Management', 'description' => 'Create, edit, or delete users'],
            ['title' => 'Archives', 'description' => 'Create and assign roles'],
            ['title' => 'Assign Task', 'description' => 'Assign tasks to users'],
            ['title' => 'Reports', 'description' => 'Generate system reports'],
            ['title' => 'Notify Users', 'description' => 'Send notifications to users']
        ];
    }

    /**
     * Get user-specific actions
     *
     * @return array
     */
    private function getUserActions()
    {
        return [
            ['title' => 'Edit Profile', 'description' => 'Update your account information'],
            ['title' => 'Settings', 'description' => 'Configure your preferences'],
            ['title' => 'Security', 'description' => 'Manage your password and sessions']
        ];
    }
}
