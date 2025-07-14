<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
// use App\Traits\ApiResponseTrait;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    // use ApiResponseTrait;



    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // if (! $request->user()->roles()->where('name', $role)->exists()) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        // return $next($request);
        // $user = $request->user();

        // if (!$user) {
        //     return $this->unifiedResponse(false, 'Unauthorized.', [], [], 401);
        // }

        // if ($user->roles()->whereIn('name', $roles)->exists()) {
        //     return $next($request);
        // }

        // return $this->unifiedResponse(false, 'Forbidden. Only allowed for: ' . implode(', ', $roles), [], [], 403);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'data'    => [],
                'errors'  => [],
                'status'  => 401,
            ], 401);
        }

        if ($user->roles()->whereIn('name', $roles)->exists()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden. Only allowed for: ' . implode(', ', $roles),
            'data'    => [],
            'errors'  => [],
            'status'  => 403,
        ], 403);
    }
}
