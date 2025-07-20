<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureDoctorApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // فقط إذا كان المستخدم طبيب
        if ($user->roles->contains('name', 'doctor'))
{
            $profile = $user->doctorProfile;

            if (!$profile || $profile->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your profile is under review or rejected. Access denied.',
                ], 403);
            }
        }

        return $next($request);
    }
}
