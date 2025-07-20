<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFreelancerIsPhoneAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $freelancer = $request->user();

        if (! $freelancer || $freelancer->provider !== 'phone') {
            return response()->json([
                'status' => 403,
                'message' => ' only allowed for phone-authenticated  .',
            ], 403);
        }

        return $next($request);
    }
}
