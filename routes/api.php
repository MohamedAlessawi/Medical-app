<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\AdminUserController;
use \App\Http\Controllers\Api\Patient\PatientProfileController;
use App\Http\Controllers\Api\Doctor\DoctorProfileController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
route::post('register',[RegisterController::class, 'register']);
Route::post('verify-email', [RegisterController::class, 'verifyEmail']);
Route::middleware('throttle:2,10')->post('resend-verification-code',[RegisterController::class,'resendVerificationCode'])
    ->name('resend.verification.code');;
Route::post('login', [LoginController::class, 'login'])
    ->name('login');
Route::post('refresh-token',[LoginController::class, 'refresh']);
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');


Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetCode']);
Route::post('reset-password', [ForgotPasswordController::class, 'reset']);

Route::post('/admin/add-user-role', [AdminUserController::class, 'addUserRole'])->middleware('auth:sanctum', 'role:admin');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('2fa/disable', [TwoFactorController::class, 'disable']);
    Route::post('2fa/verify', [TwoFactorController::class, 'verify']);


});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//patient
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/patient/profile', [PatientProfileController::class, 'show']);
    Route::put('/patient/profile', [PatientProfileController::class, 'update']);
});
//Doctor
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::post('doctor/profile', [DoctorProfileController::class, 'storeOrUpdate']);
    Route::get('doctor/profile', [DoctorProfileController::class, 'show']);
});




