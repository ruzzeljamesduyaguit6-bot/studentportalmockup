<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
    ->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
    ->name('password.update');

Route::get('/dashboard', function () {
    return view('admin.dashboard');
})->name('dashboard');

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

Route::get('/professors/dashboard', function () {
    return view('professors.dashboard');
})->name('professors.dashboard');

Route::get('/users', function () {
    return view('admin.users');
})->name('users.management');

Route::get('/designations', function () {
    return view('admin.designations');
})->name('designations.management');

Route::get('/departments', function () {
    return view('admin.departments');
})->name('departments.management');

Route::get('/courses', function () {
    return view('admin.courses');
})->name('courses.management');

Route::get('/subjects', function () {
    return view('admin.subjects');
})->name('subjects.management');

Route::get('/notifications', function () {
    return view('admin.notifications');
})->name('notifications');

Route::get('/messages', function () {
    return view('admin.messages');
})->name('messages');

Route::get('/analytics', function () {
    return view('admin.dashboard');
})->name('analytics');

Route::get('/reports', function () {
    return view('admin.dashboard');
})->name('reports');

Route::get('/profile', function () {
    return view('admin.profile');
})->name('profile');

Route::get('/settings', function () {
    return view('admin.dashboard');
})->name('settings');



