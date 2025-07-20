<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFreelancerHasProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $freelancer = $request->user();

        if (! $freelancer || ! $freelancer->profile) {
            return response()->json([
                'status' => 403,
                'message' => 'You must complete your profile before accessing this .'
            ], 403);
        }

        return $next($request);
    }
}
