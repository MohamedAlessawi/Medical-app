<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\AdminUserController;
use \App\Http\Controllers\Api\Patient\PatientProfileController;
use \App\Http\Controllers\Api\Patient\PatientAppointmentController;


use App\Http\Controllers\Api\Doctor\DoctorAppointmentController;
use App\Http\Controllers\Api\Doctor\DoctorCenterController;
use App\Http\Controllers\Api\Doctor\DoctorPatientProfileController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Secretary\PatientController;
use App\Http\Controllers\Secretary\DoctorController;

use App\Http\Controllers\SuperAdmin\DoctorApprovalController;
use App\Http\Controllers\SuperAdmin\LicenseController;
use App\Http\Controllers\SuperAdmin\CenterController;
use App\Http\Controllers\SuperAdmin\CenterAdminController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\Secretary\SecretaryProfileController;
use App\Http\Controllers\Secretary\AppointmentRequestController;
use App\Http\Controllers\Api\Doctor\DoctorProfileController;
use App\Http\Controllers\SearchController;


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

    Route::get('/patient/centers', [PatientAppointmentController::class, 'getCenters']);

    Route::get('/patient/specialties', [PatientAppointmentController::class, 'getSpecialties']);
    Route::get('/patient/centers/{centerId}/specialties/{specialtyId}/doctors', [PatientAppointmentController::class, 'getDoctorsByCenterAndSpecialty']);
    Route::get('/patient/doctors/{doctorId}/centers', [PatientAppointmentController::class, 'getDoctorCenters']);


    Route::get('/patient/doctors/{doctorId}/centers/{centerId}/available-slots', [PatientAppointmentController::class, 'getAvailableSlots']);


    Route::post('/patient/appointment-requests', [PatientAppointmentController::class, 'requestAppointment']);
    Route::get('/patient/appointment-requests', [PatientAppointmentController::class, 'getAppointmentRequests']);


});
//Doctor
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::post('doctor/profile', [DoctorProfileController::class, 'storeOrUpdate']);
    Route::get('doctor/profile', [DoctorProfileController::class, 'show']);
    Route::get('doctor/appointments', [DoctorAppointmentController::class, 'index']);
    Route::get('doctor/appointments/{id}', [DoctorAppointmentController::class, 'show']);
    Route::get('doctor/past-appointments', [DoctorAppointmentController::class, 'pastAppointments']);
    Route::get('doctor/appointments/patient/{patientId}/visits', [DoctorAppointmentController::class, 'pastVisits']);
    Route::get('doctor/centers', [DoctorCenterController::class, 'index']);
    Route::get('doctor/patients/{patientId}/profile', [DoctorPatientProfileController::class, 'show']);
});


//Super-Admin
Route::middleware(['auth:sanctum', 'role:super_admin'])
    ->post('/superadmin/register-center-admin', [SuperAdminController::class, 'registerCenterAdmin']);


// //Super-Admin
// Route::middleware(['auth:sanctum', 'role:super_admin'])
//     ->post('/superadmin/register-center-admin', [SuperAdminController::class, 'registerCenterAdmin']);

Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/doctors/pending', [DoctorApprovalController::class, 'listPending']);
    Route::post('/doctors/{id}/approve', [DoctorApprovalController::class, 'approve']);
    Route::post('/doctors/{id}/reject', [DoctorApprovalController::class, 'reject']);

});

Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('superadmin')->group(function () {
    //انشاء مركز وربطه مع المدير
    Route::post('/register-center-admin', [SuperAdminController::class, 'registerCenterAdmin']);

    // تراخيص المراكز
    Route::get('/licenses', [LicenseController::class, 'index']);
    Route::get('/licenses/{id}', [LicenseController::class, 'show']);
    Route::put('/licenses/{id}/status', [LicenseController::class, 'updateStatus']);

    // إدارة المراكز
    Route::get('/centers', [CenterController::class, 'index']);
    Route::get('/centers/{id}', [CenterController::class, 'show']);
    Route::put('/centers/{id}', [CenterController::class, 'update']);
    Route::put('/centers/{id}/toggle-status', [CenterController::class, 'toggleStatus']);

    // مدراء المراكز
    Route::get('/center-admins', [CenterAdminController::class, 'index']);
    Route::get('/center-admins/{id}', [CenterAdminController::class, 'show']);
    Route::put('/center-admins/{id}', [CenterAdminController::class, 'update']);
    Route::put('/center-admins/{id}/toggle-status', [CenterAdminController::class, 'toggleStatus']);

    // إدارة الحسابات والصلاحيات
    Route::put('/users/{id}/toggle-status', [UserManagementController::class, 'toggleStatus']);
    Route::post('/users/{id}/assign-role', [UserManagementController::class, 'assignRole']);

    // الإحصائيات والتقارير
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStats']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{id}', [ReportController::class, 'show']);
});


//Secretary
Route::middleware(['auth:sanctum', 'role:secretary'])->prefix('secretary')->group(function () {

    Route::get('/doctors/search', [DoctorController::class, 'search']);
    Route::get('/patients/search', [PatientController::class, 'search']);

    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::put('/patients/{id}/profile', [PatientController::class, 'updateProfile']);
    Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{id}', [DoctorController::class, 'show']);

    Route::get('/doctors/{id}/working-hours', [DoctorController::class, 'getWorkingHours']);
    Route::post('/doctors/{id}/working-hours', [DoctorController::class, 'storeWorkingHour']);
    Route::put('/doctors/working-hours/{hour_id}', [DoctorController::class, 'updateWorkingHour']);
    Route::delete('/doctors/working-hours/{hour_id}', [DoctorController::class, 'deleteWorkingHour']);

    Route::get('/doctors/search', [DoctorController::class, 'search']);
    Route::get('/patients/search', [PatientController::class, 'search']);

    /////////////////////////
    Route::get('/doctors/{id}/appointments', [DoctorController::class, 'getAppointments']);
    Route::post('/doctors/book-appointment', [DoctorController::class, 'bookAppointment']);
    Route::put('/appointments/{id}', [DoctorController::class, 'updateAppointment']);
    Route::delete('/appointments/{id}', [DoctorController::class, 'deleteAppointment']);
    Route::put('/appointments/{id}/attendance', [DoctorController::class, 'confirmAttendance']);

    Route::get('/dashboard-stats', [DoctorController::class, 'dashboardStats']);
    Route::get('/appointments/today', [DoctorController::class, 'todaysAppointmentsForCenter']);
    Route::post('/patients/{id}/upload-medical-file', [PatientController::class, 'uploadMedicalFile']);


    Route::get('/profile', [SecretaryProfileController::class, 'getProfile']);
    Route::put('/profile', [SecretaryProfileController::class, 'updateProfile']);
    Route::post('/profile/photo', [SecretaryProfileController::class, 'updateProfilePhoto']);


    Route::get('/appointment-requests', [AppointmentRequestController::class, 'index']);
    Route::get('/appointment-requests/{id}', [AppointmentRequestController::class, 'show']);
    Route::post('/appointment-requests/{id}/approve', [AppointmentRequestController::class, 'approve']);
    Route::post('/appointment-requests/{id}/reject', [AppointmentRequestController::class, 'reject']);
    Route::get('/appointment-requests-stats', [AppointmentRequestController::class, 'stats']);
});

// Search routes - متاحة للجميع
Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/advanced', [SearchController::class, 'advancedSearch']);
Route::get('/search/specialties', [SearchController::class, 'searchSpecialties']);
Route::get('/search/doctors', [SearchController::class, 'searchDoctors']);
Route::get('/search/centers', [SearchController::class, 'searchCenters']);
