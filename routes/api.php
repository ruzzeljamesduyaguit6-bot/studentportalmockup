<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CatalogManagementController;
use App\Http\Controllers\ProfileController;
use App\Models\User;

// Public authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);

// Dashboard routes (protected - require Bearer token)
Route::get('/dashboard/data', [DashboardController::class, 'getData']);

// User Management routes (protected - require Bearer token)
Route::get('/user-management/data', [UserManagementController::class, 'getPageData']);
Route::get('/user-management/users', [UserManagementController::class, 'getUsers']);
Route::post('/user-management/users', [UserManagementController::class, 'store']);
Route::delete('/user-management/users/{id}', [UserManagementController::class, 'deleteUser']);
Route::put('/user-management/users/{id}', [UserManagementController::class, 'updateUser']);
Route::post('/user-management/users/bulk-delete', [UserManagementController::class, 'bulkDeleteUsers']);

// Profile routes
Route::get('/profile', [ProfileController::class, 'getProfile']);
Route::get('/profile/options', [ProfileController::class, 'getProfileOptions']);
Route::put('/profile', [ProfileController::class, 'updateProfile']);
Route::post('/profile/password', [ProfileController::class, 'changePassword']);
Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
Route::post('/profile/email-verification/send', [ProfileController::class, 'sendEmailVerificationCode']);
Route::post('/profile/email-verification/verify', [ProfileController::class, 'verifyEmailCode']);

// Notifications routes
Route::get('/notifications', [ProfileController::class, 'getNotifications']);
Route::post('/notifications/{id}/read', [ProfileController::class, 'markNotificationRead'])
    ->whereNumber('id');

// Admin approval routes
Route::post('/profile-change-requests/{id}/approve', [ProfileController::class, 'approveProfileRequest'])
    ->whereNumber('id');
Route::post('/profile-change-requests/{id}/reject', [ProfileController::class, 'rejectProfileRequest'])
    ->whereNumber('id');

// Messages routes
Route::get('/messages/bootstrap', [MessageController::class, 'bootstrap']);
Route::get('/messages/users/search', [MessageController::class, 'searchUsers']);
Route::get('/messages/global', [MessageController::class, 'getGlobalMessages']);
Route::post('/messages/global', [MessageController::class, 'sendGlobalMessage']);
Route::get('/messages/private/{userId}', [MessageController::class, 'getPrivateMessages']);
Route::post('/messages/private/{userId}', [MessageController::class, 'sendPrivateMessage']);
Route::post('/messages/{messageId}/react', [MessageController::class, 'toggleReaction']);

// Catalog routes
Route::get('/catalog/options', [CatalogManagementController::class, 'options']);
Route::get('/catalog/subjects/courses', [CatalogManagementController::class, 'subjectCourses']);
Route::get('/catalog/courses/subjects', [CatalogManagementController::class, 'courseSubjects']);
Route::get('/catalog/courses/{id}/subjects', [CatalogManagementController::class, 'getCourseSubjectAssignments'])
    ->whereNumber('id');
Route::get('/catalog/subjects/{id}/courses', [CatalogManagementController::class, 'getSubjectCourseAssignments'])
    ->whereNumber('id');
Route::get('/catalog/departments/{id}/courses', [CatalogManagementController::class, 'getDepartmentCourseAssignments'])
    ->whereNumber('id');
Route::put('/catalog/courses/{id}/subjects', [CatalogManagementController::class, 'updateCourseSubjects'])
    ->whereNumber('id');
Route::put('/catalog/subjects/{id}/courses', [CatalogManagementController::class, 'updateSubjectCourses'])
    ->whereNumber('id');
Route::put('/catalog/departments/{id}/courses', [CatalogManagementController::class, 'updateDepartmentCourses'])
    ->whereNumber('id');
Route::get('/catalog/{type}', [CatalogManagementController::class, 'index'])
    ->where('type', 'designations|departments|courses|subjects');
Route::post('/catalog/{type}', [CatalogManagementController::class, 'store'])
    ->where('type', 'designations|departments|courses|subjects');
Route::delete('/catalog/{type}/{id}', [CatalogManagementController::class, 'destroy'])
    ->where('type', 'designations|departments|courses|subjects');

// Debug routes
Route::get('/debug/users', function () {
    $users = User::all(['id', 'name', 'email', 'user_type'])->toArray();
    return response()->json([
        'users' => $users,
        'total' => count($users)
    ]);
});

Route::post('/debug/test-password', function (Request $request) {
    $email = $request->input('email', 'admin@example.com');
    $password = $request->input('password', 'password');
    
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        return response()->json([
            'found' => false,
            'message' => 'User not found'
        ]);
    }
    
    $passwordMatch = Hash::check($password, $user->password);
    
    return response()->json([
        'found' => true,
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'password_match' => $passwordMatch,
        'stored_hash' => $user->password
    ]);
});
