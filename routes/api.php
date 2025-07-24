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
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Secretary\PatientController;
use App\Http\Controllers\Secretary\DoctorController;
use App\Http\Controllers\SuperAdmin\DoctorApprovalController;






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
Route::post('/doctor/register', [RegisterController::class, 'registerDoctor']);
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

Route::middleware(['auth:sanctum', 'role:doctor', 'doctor.approved'])->group(function () {
    Route::get('doctor/profile', [DoctorProfileController::class, 'show']);
    Route::post('doctor/profile', [DoctorProfileController::class, 'storeOrUpdate']);

});




//Super-Admin
Route::middleware(['auth:sanctum', 'role:super_admin'])
    ->post('/superadmin/register-center-admin', [SuperAdminController::class, 'registerCenterAdmin']);
Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/doctors/pending', [DoctorApprovalController::class, 'listPending']);
    Route::post('/doctors/{id}/approve', [DoctorApprovalController::class, 'approve']);
    Route::post('/doctors/{id}/reject', [DoctorApprovalController::class, 'reject']);
});



//Secretary
Route::middleware(['auth:sanctum', 'role:secretary'])->prefix('secretary')->group(function () {
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::put('/patients/{id}/profile', [PatientController::class, 'updateProfile']);

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{id}', [DoctorController::class, 'show']);

    Route::get('/doctors/{id}/working-hours', [DoctorController::class, 'getWorkingHours']);
    Route::post('/doctors/{id}/working-hours', [DoctorController::class, 'storeWorkingHour']);
    Route::put('/doctors/working-hours/{hour_id}', [DoctorController::class, 'updateWorkingHour']);
    Route::delete('/doctors/working-hours/{hour_id}', [DoctorController::class, 'deleteWorkingHour']);

    Route::get('/doctors/search', [DoctorController::class, 'search']);
    Route::get('/patients/search', [PatientController::class, 'search']);
});
