<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    /**
     * Create a new user from admin panel modal.
     */
    public function store(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $admin = User::where('api_token', $hashedToken)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        if ($admin->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birthday' => 'required|date|before:today',
            'contact' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'user_type' => 'required|in:student,professor',
            'designation' => 'nullable|required_if:user_type,professor|string|max:255|exists:designations,name',
            'department' => 'required|string|max:255|exists:departments,name',
            'course' => 'nullable|required_if:user_type,student|string|max:255|exists:courses,name',
            'subject' => 'nullable|string|max:255|exists:subjects,name',
            'year_level' => 'nullable|required_if:user_type,student|string|max:50',
        ]);

        if ($validated['user_type'] === 'student') {
            $this->assertStudentCourseMatchesDepartment($validated['course'] ?? null, $validated['department'] ?? null);
            $this->assertYearLevelMatchesCourse($validated['course'] ?? null, $validated['year_level'] ?? null);
        }

        $newUser = User::create([
            'name' => $validated['name'],
            'birthday' => $validated['birthday'],
            'contact' => $validated['contact'],
            'designation' => $validated['user_type'] === 'professor' ? $validated['designation'] : null,
            'department' => $validated['department'],
            'course' => $validated['user_type'] === 'student' ? $validated['course'] : null,
            'subject' => $validated['subject'] ?? null,
            'year_level' => $validated['user_type'] === 'student' ? $validated['year_level'] : null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_type' => $validated['user_type'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $newUser,
        ], 201);
    }

    private function assertYearLevelMatchesCourse(?string $courseName, ?string $yearLevel): void
    {
        $courseValue = trim((string) $courseName);
        $yearValue = trim((string) $yearLevel);

        if ($courseValue === '' || $yearValue === '') {
            return;
        }

        $course = DB::table('courses')
            ->where('name', $courseValue)
            ->first(['total_years']);

        if (!$course || !$course->total_years) {
            return;
        }

        if (!preg_match('/(\d+)/', $yearValue, $matches)) {
            return;
        }

        $levelNumber = (int) $matches[1];
        $maxYears = (int) $course->total_years;

        if ($levelNumber < 1 || $levelNumber > $maxYears) {
            throw ValidationException::withMessages([
                'year_level' => ['Selected year level is not valid for the selected course.'],
            ]);
        }
    }

    private function assertStudentCourseMatchesDepartment(?string $courseName, ?string $departmentName): void
    {
        $courseValue = trim((string) $courseName);
        $departmentValue = trim((string) $departmentName);

        if ($courseValue === '' || $departmentValue === '') {
            return;
        }

        $courseDepartment = DB::table('courses')
            ->leftJoin('departments', 'courses.department_id', '=', 'departments.id')
            ->where('courses.name', $courseValue)
            ->value('departments.name');

        if (!$courseDepartment) {
            return;
        }

        if (trim((string) $courseDepartment) !== $departmentValue) {
            throw ValidationException::withMessages([
                'course' => ['Selected course does not belong to the selected department.'],
            ]);
        }
    }

    /**
     * Get all users with statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers(Request $request) 
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
        $user = User::where('api_token', $hashedToken)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        // Check if user is admin
        if ($user->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        // Get all users
        $users = User::all([
            'id',
            'name',
            'email',
            'birthday',
            'contact',
            'designation',
            'department',
            'course',
            'subject',
            'year_level',
            'user_type',
            'user_code',
            'email_verified_at',
            'profile_photo_url',
            'created_at'
        ])->toArray();

        // Calculate statistics
        $totalUsers = count($users);
        $adminUsers = count(array_filter($users, fn($u) => $u['user_type'] === 'admin'));
        $studentUsers = count(array_filter($users, fn($u) => $u['user_type'] === 'student'));
        $professorUsers = count(array_filter($users, fn($u) => $u['user_type'] === 'professor'));

        return response()->json([
            'success' => true,
            'users' => $users,
            'stats' => [
                'totalUsers' => $totalUsers,
                'admins' => $adminUsers,
                'students' => $studentUsers,
                'professors' => $professorUsers
            ],
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type
            ]
        ]);
    }

    /**
     * Get initial page data for user management
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPageData(Request $request)
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
        $user = User::where('api_token', $hashedToken)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        // Check if user is admin
        if ($user->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Calculate initials
        $nameParts = explode(' ', trim($user->name));
        $initials = '';
        if (count($nameParts) >= 2) {
            $initials = strtoupper($nameParts[0][0] . $nameParts[count($nameParts) - 1][0]);
        } else {
            $initials = strtoupper($nameParts[0][0]);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'initials' => $initials
            ]
        ]);
    }

    /**
     * Delete a user
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUser(Request $request, $id)
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
        $admin = User::where('api_token', $hashedToken)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        // Check if requester is admin
        if ($admin->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        // Find and delete the user
        $userToDelete = User::find($id);

        if (!$userToDelete) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($userToDelete->user_type === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be deleted'
            ], 403);
        }

        $userToDelete->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Update a user
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request, $id)
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
        $admin = User::where('api_token', $hashedToken)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        // Check if requester is admin
        if ($admin->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        // Find the user to update
        $userToUpdate = User::find($id);

        if (!$userToUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birthday' => 'required|date|before:today',
            'contact' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'user_type' => 'required|in:student,professor',
            'designation' => 'nullable|required_if:user_type,professor|string|max:255|exists:designations,name',
            'department' => 'required|string|max:255|exists:departments,name',
            'course' => 'nullable|required_if:user_type,student|string|max:255|exists:courses,name',
            'subject' => 'nullable|string|max:255|exists:subjects,name',
            'year_level' => 'nullable|required_if:user_type,student|string|max:50',
        ]);

        if ($validated['user_type'] === 'professor') {
            $validated['course'] = null;
            $validated['year_level'] = null;
        } else {
            $validated['designation'] = null;
            $this->assertStudentCourseMatchesDepartment($validated['course'] ?? null, $validated['department'] ?? null);
            $this->assertYearLevelMatchesCourse($validated['course'] ?? null, $validated['year_level'] ?? null);
        }

        // Update the user
        $userToUpdate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $userToUpdate
        ]);
    }

    /**
     * Bulk delete users.
     */
    public function bulkDeleteUsers(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $admin = User::where('api_token', $hashedToken)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        if ($admin->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id'
        ]);

        $adminInSelection = User::whereIn('id', $validated['ids'])
            ->where('user_type', 'admin')
            ->exists();

        if ($adminInSelection) {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be deleted'
            ], 403);
        }

        $deletedCount = User::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} user(s) successfully"
        ]);
    }
}

