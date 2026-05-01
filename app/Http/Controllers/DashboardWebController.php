<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardWebController extends Controller
{
    /**
     * Show the dashboard
     */
    public function show(Request $request)
    {
        // The dashboard view checks for authentication and token in localStorage via JavaScript
        // If no token exists, the client-side code will redirect to /login
        // Default isAdmin to false - the client will set the actual role from localStorage
        return view('admin.dashboard', [
            'isAdmin' => false
        ]);
    }
}
